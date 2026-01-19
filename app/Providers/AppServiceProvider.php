<?php

namespace App\Providers;

use App\Repositories\Contracts\QueueTicketRepositoryInterface;
use App\Repositories\Eloquent\QueueTicketRepository;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            QueueTicketRepositoryInterface::class,
            QueueTicketRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi) {
                $openApi->secure(
                    SecurityScheme::http('bearer', 'JWT')
                );
            });

        // Force HTTPS pada production
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Trust proxies (untuk X-Forwarded-Proto header)
        $this->app['request']->server->set('HTTPS', 'on');
    }
}
