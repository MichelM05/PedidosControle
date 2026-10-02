<?php

namespace App\Providers;

use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Observers\PedidoItemObserver;
use App\Observers\PedidoObserver;
use App\Services\HistoricoService;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;
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

        if ($proxies = config('app.trusted_proxies')) {
            TrustProxies::at($proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }
        if (config('app.force_https')) {
            URL::forceHttps();
            config(['session.secure' => true]);
        }

        Pedido::observe(PedidoObserver::class);
        PedidoItem::observe(PedidoItemObserver::class);
    }
}
