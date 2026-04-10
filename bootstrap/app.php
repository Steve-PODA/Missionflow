<?php

use App\Console\Commands\SendMissionDayAlerts;
use App\Console\Commands\SendMissionReminderAlerts;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'role'       => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        // Rappel J-1 : envoyé chaque soir à 18h00
        $schedule->command(SendMissionReminderAlerts::class)->dailyAt('18:00');

        // Alerte Jour J : envoyée chaque matin à 07h00
        $schedule->command(SendMissionDayAlerts::class)->dailyAt('07:00');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
