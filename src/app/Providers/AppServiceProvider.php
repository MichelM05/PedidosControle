<?php

namespace App\Providers;

use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Observers\PedidoItemObserver;
use App\Observers\PedidoObserver;
use App\Services\HistoricoService;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Uma instância por requisição: todas as alterações dela saem no mesmo lote do histórico
        $this->app->scoped(HistoricoService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        JsonResource::withoutWrapping();

        Pedido::observe(PedidoObserver::class);
        PedidoItem::observe(PedidoItemObserver::class);
    }
}
