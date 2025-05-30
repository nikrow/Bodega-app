<?php

namespace App\Providers;




use Livewire\Livewire;
use App\Models\Movimiento;
use App\Models\Fertilization;
use App\Services\StockService;
use App\Models\OrderApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\Login;
use App\Listeners\UpdateLoginTime;
use App\Models\MovimientoProducto;
use Illuminate\Auth\Events\Logout;
use App\Policies\ActivityLogPolicy;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Gate;
use App\Observers\MovimientoObserver;
use App\Policies\FertilizationPolicy;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Models\Activity;
use App\Observers\OrderApplicationObserver;
use App\Observers\MovimientoProductoObserver;
use App\Listeners\UpdateActiveMinutesOnLogout;
use App\Console\Commands\UpdateMissingOrderApplicationUsage;



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

        // Define macros to override signature verification
        URL::macro('alternateHasCorrectSignature', function (Request $request, $absolute = true, array $ignoreQuery = []) {
            $ignoreQuery[] = 'signature';

            // Use url() helper to respect forced scheme and root URL
            $absoluteUrl = url($request->path());
            $url = $absolute ? $absoluteUrl : '/' . $request->path();

            $queryString = collect(explode('&', (string) $request->server->get('QUERY_STRING')))
                ->reject(fn ($parameter) => in_array(Str::before($parameter, '='), $ignoreQuery))
                ->join('&');

            $original = rtrim($url . '?' . $queryString, '?');

            // Generate signature using the application key
            $signature = hash_hmac('sha256', $original, call_user_func($this->keyResolver));
            return hash_equals($signature, (string) $request->query('signature', ''));
        });

        URL::macro('alternateHasValidSignature', function (Request $request, $absolute = true, array $ignoreQuery = []) {
            return URL::alternateHasCorrectSignature($request, $absolute, $ignoreQuery)
                && URL::signatureHasNotExpired($request);
        });

        Request::macro('hasValidSignature', function ($absolute = true, array $ignoreQuery = []) {
            return URL::alternateHasValidSignature($this, $absolute, $ignoreQuery);
        });
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
        Gate::policy(Fertilization::class, FertilizationPolicy::class);
        Movimiento::observe(MovimientoObserver::class);
        MovimientoProducto::observe(MovimientoProductoObserver::class);
        OrderApplication::observe(OrderApplicationObserver::class);
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
