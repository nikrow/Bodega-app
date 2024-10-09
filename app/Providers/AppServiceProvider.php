<?php

namespace App\Providers;

use App\Models\Movimiento;
use App\Models\MovimientoProducto;
use App\Observers\MovimientoObserver;
use App\Observers\MovimientoProductoObserver;
use Illuminate\Support\ServiceProvider;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        MovimientoProducto::observe(MovimientoProductoObserver::class);
    }
}
