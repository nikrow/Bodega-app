<?php

namespace App\Providers;



use App\Console\Commands\UpdateMissingOrderApplicationUsage;
use App\Listeners\UpdateLoginTime;
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
use App\Policies\ActivityLogPolicy;
use App\Services\StockService;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use App\Policies\CropPolicy;
use App\Policies\WarehousePolicy;
use App\Listeners\UpdateActiveMinutesOnLogout;
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
        Login::class => [
            UpdateLoginTime::class,
        ],
        Logout::class => [
            UpdateActiveMinutesOnLogout::class,
        ],
    ];
    protected $commands = [
        UpdateMissingOrderApplicationUsage::class,
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
