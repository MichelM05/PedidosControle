<?php

namespace App\Http\Controllers;

use App\Http\Requests\AtualizarItemControleRequest;
use App\Http\Requests\FiltroControleRequest;
use App\Http\Resources\ControleLinhaResource;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Services\ControleService;
use App\Services\PlanilhaControleExporter;
use App\Support\ColunasControle;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Controle de pedidos em formato de planilha: grade por ano, edição por célula e exportação .xlsx. */
class ControleController extends Controller
{
    public function __construct(private ControleService $controle, private PlanilhaControleExporter $exportador) {}

    public function index(FiltroControleRequest $request)
    {
        $filtros = $request->validated();
        $anos = $this->controle->anos();
        $ano = (int) ($filtros['ano'] ?? $anos->first() ?? now()->year);

        $todos = $this->controle->itensDoAno($ano);

        return Inertia::render('Controle/Index', [
            'ano' => $ano,
            'anos' => $anos->contains($ano) ? $anos : $anos->push($ano)->sortDesc()->values(),
            'filtros' => (object) $filtros,
            'linhas' => ControleLinhaResource::collection($this->controle->itensDoAno($ano, $filtros))->resolve(),
            'totais' => collect(PedidoItem::STATUS)->map(fn ($rotulo, $chave) => $todos->where('status', $chave)->count())->all() + ['todos' => $todos->count()],
            'colunas' => ColunasControle::lista(),
            'status' => PedidoItem::STATUS,
            'responsaveis' => $this->controle->responsaveis(),
            'diasAlerta' => ColunasControle::DIAS_ALERTA,
            'cores' => [
                'linha' => ColunasControle::COR_LINHA,
                'entregue' => ColunasControle::COR_ENTREGUE,
                'finalizado' => ColunasControle::COR_FINALIZADO,
                'prazo' => ColunasControle::COR_PRAZO,
            ],
        ]);
    }

    /** Salva uma célula da grade. */
    public function atualizar(AtualizarItemControleRequest $request, PedidoItem $item)
    {
        $item->update([$request->validated('campo') => $request->validated('valor')]);

        return back();
    }

    /** Planilha de um ano (?ano=2026) ou de todos os anos, uma aba por ano. */
    public function exportar(FiltroControleRequest $request): StreamedResponse
    {
        $ano = $request->validated('ano');
        $anos = $ano ? collect([(int) $ano]) : $this->controle->anos();

        $abas = $anos->mapWithKeys(fn (int $a) => [$a => $this->controle->itensDoAno($a)])->all();

        return $this->baixar($abas, $ano ? "controle-de-pedidos-$ano.xlsx" : 'controle-de-pedidos.xlsx');
    }

    /** Planilha com os itens de um único pedido (na aba do ano dele). */
    public function exportarPedido(Pedido $pedido): StreamedResponse
    {
        return $this->baixar(
            [$pedido->anoDoControle() => $this->controle->itensDoPedido($pedido)],
            'pedido-'.preg_replace('/[^\w.-]+/u', '-', (string) ($pedido->numero ?? $pedido->id)).'.xlsx',
        );
    }

    private function baixar(array $abas, string $arquivo): StreamedResponse
    {
        $escritor = new Xlsx($this->exportador->gerar($abas));
        $escritor->setPreCalculateFormulas(false);

        return response()->streamDownload(fn () => $escritor->save('php://output'), $arquivo, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
