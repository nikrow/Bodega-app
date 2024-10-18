<?php

namespace App\Providers;

use App\Filament\Resources\ConsolidatedOrderResource;
use App\Models\Movimiento;
use App\Models\MovimientoProducto;
use App\Models\OrderAplication;
use App\Observers\MovimientoObserver;
use App\Observers\MovimientoProductoObserver;
use App\Observers\OrderAplicationObserver;
use Illuminate\Support\ServiceProvider;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */


    public function register(): void
    {

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        MovimientoProducto::observe(MovimientoProductoObserver::class);
        OrderAplication::observe(OrderAplicationObserver::class);

    }
}
