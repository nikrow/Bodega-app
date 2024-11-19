<?php

namespace App\Providers;


use App\Models\Crop;
use App\Models\Movimiento;
use App\Models\MovimientoProducto;
use App\Models\OrderApplication;

use App\Models\OrderApplicationUsage;
use App\Models\User;
use App\Models\Warehouse;
use App\Observers\MovimientoObserver;
use App\Observers\MovimientoProductoObserver;
use App\Observers\OrderApplicationObserver;
use App\Observers\OrderApplicationUsageObserver;
use App\Policies\ActivityLogPolicy;
use App\Services\StockService;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
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
        $this->app->singleton(StockService::class, function ($app) {
            return new StockService();
        });
        Gate::policy(Activity::class, ActivityLogPolicy::class);
    }
    protected $listen = [
        \Illuminate\Auth\Events\Login::class => [
            \App\Listeners\UpdateLoginTime::class,
        ],
        \Illuminate\Auth\Events\Logout::class => [
            \App\Listeners\UpdateActiveHoursOnLogout::class,
        ],
    ];
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Movimiento::observe(MovimientoObserver::class);
        MovimientoProducto::observe(MovimientoProductoObserver::class);
        OrderApplication::observe(OrderApplicationObserver::class);
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
