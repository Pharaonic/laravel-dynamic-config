<?php

declare(strict_types=1);

namespace Pharaonic\Laravel\DynamicConfig;

use Illuminate\Config\Repository;
use Illuminate\Support\ServiceProvider;

final class DynamicConfigServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/dynamic-config.php', 'pharaonic.dynamic-config');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        Repository::macro('dynamic', fn (?string $identifier = null): DynamicConfig => new DynamicConfig($identifier));

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/dynamic-config.php' => $this->app->configPath('pharaonic/dynamic-config.php'),
            ], ['pharaonic', 'laravel-dynamic-config', 'pharaonic-config', 'dynamic-config-config']);

            $this->publishes([
                __DIR__.'/../database/migrations' => $this->app->databasePath('migrations'),
            ], ['pharaonic', 'laravel-dynamic-config', 'pharaonic-migrations', 'dynamic-config-migrations']);
        }
    }
}
