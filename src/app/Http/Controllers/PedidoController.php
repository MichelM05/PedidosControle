<?php

namespace App\Http\Controllers;

use App\Http\Requests\AtualizarDadosPedidoRequest;
use App\Http\Requests\SavePedidoRequest;
use App\Http\Requests\SearchPedidoRequest;
use App\Http\Requests\UploadPedidoRequest;
use App\Http\Resources\PedidoResource;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Services\PedidoService;
use App\Services\PedidoUploadService;
use App\Support\ColunasControle;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Throwable;

class PedidoController extends Controller
{
    public function __construct(private PedidoService $pedidos) {}

    public function index(SearchPedidoRequest $request)
    {
        $filtros = $request->validated();

        return Inertia::render('Pedidos/Index', [
            'pedidos' => Pedido::search($filtros)->paginate(15)->withQueryString()
                ->through(fn (Pedido $pedido) => PedidoResource::make($pedido)->resolve()),
            'filtros' => (object) $filtros,
            'status' => PedidoItem::STATUS,
            'cores' => ColunasControle::cores(),
            'prazos' => ColunasControle::prazos(),
        ]);
    }

    public function show(Pedido $pedido)
    {
        return Inertia::render('Pedidos/Show', [
            'pedido' => PedidoResource::make($pedido->load('itens')),
            'rotulos' => ['blocos' => Pedido::BLOCOS, 'condicoes' => Pedido::CONDICOES],
            'status' => PedidoItem::STATUS,
        ]);
    }

    public function create()
    {
        return Inertia::render('Pedidos/Form', ['pedido' => null, 'status' => PedidoItem::STATUS]);
    }

    public function edit(Pedido $pedido)
    {
        return Inertia::render('Pedidos/Form', ['pedido' => PedidoResource::make($pedido->load('itens')), 'status' => PedidoItem::STATUS]);
    }

    public function store(SavePedidoRequest $request)
    {
        $pedido = $this->pedidos->salvar(new Pedido, $request->safe()->except('itens'), $request->input('itens', []));

        return redirect()->route('pedidos.show', $pedido)->with('success', 'Pedido criado com sucesso!');
    }

    public function update(SavePedidoRequest $request, Pedido $pedido)
    {
        $this->pedidos->salvar($pedido, $request->safe()->except('itens'), $request->input('itens', []));

        return redirect()->route('pedidos.show', $pedido)->with('success', 'Pedido atualizado com sucesso!');
    }

    /** Edição por seção dos modais da tela de detalhes (não altera os itens). */
    public function atualizarDados(AtualizarDadosPedidoRequest $request, Pedido $pedido)
    {
        $this->pedidos->atualizarSecao($pedido, $request->validated('secao'), $request->safe()->except('secao'));

        return redirect()->route('pedidos.show', $pedido)->with('success', 'Dados atualizados.');
    }

    public function destroy(Pedido $pedido)
    {
        $pedido->delete();

        return redirect()->route('pedidos.index')->with('success', 'Pedido excluído com sucesso!');
    }

    /** Exibe o PDF original importado, para conferência dos dados. */
    public function pdf(Pedido $pedido)
    {
        abort_unless($pedido->arquivo_pdf && Storage::exists($pedido->arquivo_pdf), 404, 'PDF original não disponível.');

        return Storage::response($pedido->arquivo_pdf, 'pedido-'.($pedido->numero ?? $pedido->id).'.pdf', [
            'Content-Type' => 'application/pdf',
        ], 'inline');
    }

    public function upload(UploadPedidoRequest $request, PedidoUploadService $service)
    {
        try {
            $pedido = $service->processarUpload($request->file('pdf'));
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors(['pdf' => 'Erro ao processar o PDF: '.$e->getMessage()]);
        }

        return redirect()->route('pedidos.show', $pedido)->with('success', 'Pedido importado!');
    }
}
