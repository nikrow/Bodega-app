<?php

namespace App\Providers;

use App\Filament\Resources\ConsolidatedOrderResource;
use App\Models\Crop;
use App\Models\Movimiento;
use App\Models\MovimientoProducto;
use App\Models\OrderApplication;

use App\Models\User;
use App\Models\Warehouse;
use App\Observers\MovimientoObserver;
use App\Observers\MovimientoProductoObserver;
use App\Observers\OrderApplicationObserver;
use App\Policies\ActivityLogPolicy;
use Spatie\Activitylog\Models\Activity;
use App\Policies\CropPolicy;
use App\Policies\WarehousePolicy;
use Illuminate\Support\Facades\Gate;
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
        OrderApplication::observe(OrderApplicationObserver::class);


    }
}
