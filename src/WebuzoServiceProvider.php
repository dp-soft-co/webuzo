<?php

declare(strict_types=1);

namespace Webuzo;

use Illuminate\Support\ServiceProvider;

class WebuzoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/webuzo.php', 'webuzo');

        $this->app->singleton(WebuzoManager::class, function ($app) {
            return new WebuzoManager($app['config']->get('webuzo', []));
        });

        $this->app->alias(WebuzoManager::class, 'webuzo');

        $this->app->singleton(WebuzoService::class);
        $this->app->singleton(NetDataService::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/webuzo.php' => config_path('webuzo.php'),
        ], 'webuzo-config');
    }
}
