<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchPedidoRequest;
use App\Http\Requests\SavePedidoRequest;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Helpers\UtilsNormalizarNumero;
use App\Services\PedidoUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Smalot\PdfParser\Parser;

class PedidoController extends Controller
{
    public function index(SearchPedidoRequest $request)
    {
        $pedidos = Pedido::search($request->validated())->paginate(15);
        $filtros = $request->validated();
        return view('pedidos.index', compact('pedidos', 'filtros'));
    }

    public function show(Pedido $pedido)
    {
        $pedido->load('itens');
        return view('pedidos.show', compact('pedido'));
    }

    public function create()
    {
        $pedido = new Pedido();
        return view('pedidos.create', compact('pedido'));
    }

    public function edit(Pedido $pedido)
    {
        $pedido->load('itens');
        return view('pedidos.edit', compact('pedido'));
    }

    /**
     * @throws \Throwable
     */
    public function save(SavePedidoRequest $request, $id = null)
    {
        $dadosValidados = $request->validated();

        return DB::transaction(function () use ($request, $id, $dadosValidados) {

            //Buscar ou Criar o Pedido
            $pedido = $id ? Pedido::findOrFail($id) : new Pedido();

            $pedido->fill($dadosValidados);
            $pedido->save();

            //Lógica de Itens (Sincronização)
            if ($request->has('itens')) {
                // Se for uma edição, uma estratégia simples é remover os antigos e salvar os novos
                if ($id) {
                    $pedido->itens()->delete();
                }

                foreach ($request->itens as $itemDados) {
                    $pedido->itens()->create($itemDados);
                }
            }

            $mensagem = $id ? 'Pedido atualizado com sucesso!' : 'Pedido criado com sucesso!';
            return redirect()->route('pedidos.show', compact('pedido'))->with('success', $mensagem);
        });
    }

    public function delete(Pedido $pedido)
    {
        $pedido->delete();

        return redirect()->route('pedidos.index')->with('success', 'Pedido excluído com sucesso!');
    }

    public function upload(Request $request, PedidoUploadService $service)
    {
        $request->validate(['pdf' => 'required|mimes:pdf']);

        try {
            $pedido = $service->processarUpload($request->file('pdf'));
            return redirect()->route('pedidos.show', $pedido)->with('success', 'Pedido importado!');
        } catch (\Exception $e) {
            return back()->withErrors(['pdf' => 'Erro ao processar: ' . $e->getMessage()]);
        }
    }
}
