<?php


use App\Console\Commands\UpdateActiveMinutesCommand;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->group('auth', [
            // Otros middleware de autenticación si los tienes
            \App\Http\Middleware\UpdateLastActivity::class,
        ]);
    })
    ->withCommands([
        UpdateActiveMinutesCommand::class,
    ])
    ->withSchedule(function (Illuminate\Console\Scheduling\Schedule $schedule) {
        $schedule->command('users:update-active-minutes')->hourly();
        $schedule->command('inspire')->hourly();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
