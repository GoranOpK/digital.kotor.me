<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        // Ovdje registrujemo alias za role middleware
        $middleware->alias([
            // 'role' je alias koji koristimo u rutama, a desno je putanja do tvog middleware-a
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'module_access_restrict' => \App\Http\Middleware\RestrictRoleModuleAccess::class,
            'cultural.portal' => \App\Http\Middleware\EnsureCulturalEditorialPortalAccess::class,
            'cultural.moderator' => \App\Http\Middleware\EnsureActiveCulturalModerator::class,
            // 6A-CLOSE-02 — legacy admin CRUD kill-switch (cultural-events.*)
            'legacy_cultural_events_disabled' => \App\Http\Middleware\DenyLegacyCulturalEventCrud::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();