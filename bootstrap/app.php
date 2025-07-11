<?php


use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Console\Commands\UpdateActiveMinutesCommand;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');
        $middleware->group('auth', [
            // Otros middleware de autenticación si los tienes
            \App\Http\Middleware\UpdateLastActivity::class,
            EnsureFrontendRequestsAreStateful::class,
            SubstituteBindings::class,
            \Edwink\FilamentUserActivity\Http\Middleware\RecordUserActivity::class,
        ]);
        $middleware->web(append: [
            VerifyCsrfToken::class,
        ]);
    })
    ->withCommands([
        UpdateActiveMinutesCommand::class,
    ])
    ->withSchedule(function (Illuminate\Console\Scheduling\Schedule $schedule) {
        
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
