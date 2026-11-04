<?php

declare(strict_types=1);

use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Pharaonic\Laravel\DynamicConfig\DynamicConfig;
use Pharaonic\Laravel\DynamicConfig\DynamicConfigServiceProvider;

it('registers the service provider', function () {
    expect($this->app->getProviders(DynamicConfigServiceProvider::class))->not->toBeEmpty();
});

it('merges the package configuration', function () {
    expect(config('pharaonic.dynamic-config'))->toBe([
        'connection' => null,
        'table' => 'dynamic_configs',
        'cache' => [
            'enabled' => true,
            'store' => null,
            'ttl' => null,
        ],
    ]);
});

it('registers the dynamic macro on the config repository', function () {
    expect(Repository::hasMacro('dynamic'))->toBeTrue()
        ->and(Config::dynamic())->toBeInstanceOf(DynamicConfig::class)
        ->and(config()->dynamic('branch-12'))->toBeInstanceOf(DynamicConfig::class)
        ->and($this->app['config']->dynamic())->toBeInstanceOf(DynamicConfig::class);
});

it('returns a new scope on every call', function () {
    expect(Config::dynamic())->not->toBe(Config::dynamic());
});

it('publishes the config', function (string $tag) {
    expect(ServiceProvider::pathsToPublish(DynamicConfigServiceProvider::class, $tag))
        ->toContain($this->app->configPath('pharaonic/dynamic-config.php'));
})->with(['pharaonic', 'laravel-dynamic-config', 'pharaonic-config', 'dynamic-config-config']);

it('publishes the migrations', function (string $tag) {
    expect(ServiceProvider::pathsToPublish(DynamicConfigServiceProvider::class, $tag))
        ->toContain($this->app->databasePath('migrations'));
})->with(['pharaonic', 'laravel-dynamic-config', 'pharaonic-migrations', 'dynamic-config-migrations']);

it('loads the migrations', function () {
    expect(Schema::hasTable('dynamic_configs'))->toBeTrue()
        ->and(Schema::hasColumns('dynamic_configs', ['id', 'identifier', 'key', 'value', 'created_at', 'updated_at']))->toBeTrue();
});
