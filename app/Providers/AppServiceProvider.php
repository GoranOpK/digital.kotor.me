<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use App\Identity\Runtime\CurrentIdentityResolver;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\CulturalActivity\CulturalActivityStore::class);
        $this->app->singleton(\App\Services\CulturalActivity\CulturalActivityRecorder::class);
        $this->app->singleton(\App\Services\CulturalActivity\CulturalActivityEmitter::class);
        $this->app->singleton(\App\Services\ExternalArchive\ExternalFileArchiveService::class);
        $this->app->singleton(\App\Services\Payments\PaymentGatewayResolver::class);
        $this->app->singleton(\App\Security\JmbEncryptionService::class, function () {
            return \App\Security\JmbEncryptionService::fromConfig();
        });
        $this->app->singleton(\App\Security\JmbEncryptedReadService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer([
            'profile.edit',
            'dashboard',
            'applications.create',
            'admin.users.show',
            'admin.users.edit',
        ], function ($view) {
            $user = $view->getData()['user'] ?? auth()->user();
            if ($user) {
                $view->with('subjectIdentity', app(CurrentIdentityResolver::class)->viewFor($user));
            }
        });

        RateLimiter::for('ep-admin-inquiry', function (Request $request) {
            return Limit::perMinute(20)->by(
                'ep-admin-inquiry:'.(string) ($request->user()?->id ?? $request->ip())
            );
        });

        RateLimiter::for('registration', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // Event/listener registration for FT-004 uses Laravel auto-discovery
        // (App\Listeners\PublishOfficialContentNotice). Do not also Event::listen()
        // here — that would register the listener twice and publish duplicate Notices.
    }
}
