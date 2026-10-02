<?php

namespace App\Http\Controllers;

use App\Http\Requests\AtualizarControleItemRequest;
use App\Http\Requests\AtualizarStatusPedidoRequest;
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

/** Controle de pedidos em formato de planilha: grade por ano (consulta) e exportação .xlsx. A edição é na tela do pedido. */
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
            'prazos' => ColunasControle::prazos(),
            'cores' => ColunasControle::cores(),
        ]);
    }

    /** Salva o controle de produção de um item (a edição é feita na tela do pedido, não na grade). */
    public function atualizar(AtualizarControleItemRequest $request, PedidoItem $item)
    {
        $item->update($request->validated());

        return redirect()->route('pedidos.show', $item->pedido_id)->with('success', 'Controle do item atualizado.');
    }

    /** Planilha de um ano (?ano=2026) ou de todos os anos, uma aba por ano. */
    public function exportar(FiltroControleRequest $request): StreamedResponse
    {
        $ano = $request->validated('ano');
        $anos = $ano ? collect([(int) $ano]) : $this->controle->anos();

        $abas = $anos->mapWithKeys(fn (int $a) => [$a => $this->controle->itensDoAno($a)])->all();

        return $this->baixar($abas, $ano ? "controle-de-pedidos-$ano.xlsx" : 'controle-de-pedidos.xlsx');
    }

    /** Muda o status de todos os itens de um pedido de uma vez (ex.: marcar o pedido inteiro como entregue). */
    public function atualizarStatusPedido(AtualizarStatusPedidoRequest $request, Pedido $pedido)
    {
        // Item a item (não em uma única consulta) para que cada mudança entre no histórico
        $pedido->itens->each->update(['status' => $request->validated('status')]);

        return back()->with('success', 'Status de todos os itens atualizado.');
    }

    private function baixar(array $abas, string $arquivo): StreamedResponse
    {
        $escritor = new Xlsx($this->exportador->gerar($abas));
        $escritor->setPreCalculateFormulas(false);

        return response()->streamDownload(fn () => $escritor->save('php://output'), $arquivo, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
