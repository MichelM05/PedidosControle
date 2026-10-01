<?php

namespace App\Http\Controllers;

use App\Http\Requests\FiltroHistoricoRequest;
use App\Models\Historico;
use App\Models\User;
use App\Services\HistoricoService;
use Inertia\Inertia;

/** Histórico de alterações: quem mudou o quê e quando, agrupado por salvamento. */
class HistoricoController extends Controller
{
    public function index(FiltroHistoricoRequest $request, HistoricoService $historico)
    {
        $f = $request->validated();
        $q = trim($f['q'] ?? '');

        $filtrados = Historico::query()
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('pedido_numero', 'like', "%$q%")->orWhere('item_descricao', 'like', "%$q%")->orWhere('campo', 'like', "%$q%")))
            ->when(! empty($f['usuario']), fn ($query) => $query->where('user_id', $f['usuario']))
            ->when(! empty($f['acao']), fn ($query) => $query->where('acao', $f['acao']))
            ->when(! empty($f['pedido']), fn ($query) => $query->where('pedido_id', $f['pedido']))
            ->when(! empty($f['de']), fn ($query) => $query->where('created_at', '>=', $f['de'].' 00:00:00'))
            ->when(! empty($f['ate']), fn ($query) => $query->where('created_at', '<=', $f['ate'].' 23:59:59'));

        // Pagina por salvamento (lote), não por linha, para um salvamento nunca ser cortado entre páginas
        $lotes = $filtrados->selectRaw('lote, max(id) as ultimo')->groupBy('lote')->orderByDesc('ultimo')->paginate(20)->withQueryString();
        $grupos = $historico->agrupar(Historico::whereIn('lote', $lotes->pluck('lote'))->get());

        return Inertia::render('Historico/Index', [
            'grupos' => $lotes->setCollection(collect($grupos)),
            'filtros' => (object) $f,
            'usuarios' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
