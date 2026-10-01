<?php

namespace App\Http\Controllers;

use App\Http\Requests\AtualizarDadosPedidoRequest;
use App\Http\Requests\SavePedidoRequest;
use App\Http\Requests\SearchPedidoRequest;
use App\Http\Requests\UploadPedidoRequest;
use App\Models\Pedido;
use App\Services\PedidoService;
use App\Services\PedidoUploadService;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PedidoController extends Controller
{
    public function __construct(private PedidoService $pedidos) {}

    public function index(SearchPedidoRequest $request)
    {
        $filtros = $request->validated();
        $pedidos = Pedido::search($filtros)->paginate(15)->withQueryString();

        return view('pedidos.index', compact('pedidos', 'filtros'));
    }

    public function show(Pedido $pedido)
    {
        $pedido->load('itens');

        return view('pedidos.show', compact('pedido'));
    }

    public function create()
    {
        return view('pedidos.create', ['pedido' => new Pedido]);
    }

    public function edit(Pedido $pedido)
    {
        $pedido->load('itens');

        return view('pedidos.edit', compact('pedido'));
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
