<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Contracts\MegaArchiveClient::class, \App\Services\ExternalArchive\MegaArchiveService::class);
        $this->app->singleton(\App\Services\ExternalArchive\ExternalFileArchiveService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Event/listener registration for FT-004 uses Laravel auto-discovery
        // (App\Listeners\PublishOfficialContentNotice). Do not also Event::listen()
        // here — that would register the listener twice and publish duplicate Notices.
    }
}
