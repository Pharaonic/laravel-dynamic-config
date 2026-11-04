<?php

declare(strict_types=1);

namespace Pharaonic\Laravel\DynamicConfig\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\TestCase as Orchestra;
use Pharaonic\Laravel\DynamicConfig\DynamicConfigServiceProvider;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            DynamicConfigServiceProvider::class,
        ];
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('cache.stores.array', ['driver' => 'array', 'serialize' => true]);
        $app['config']->set('cache.stores.secondary', ['driver' => 'array', 'serialize' => true]);
    }

    /**
     * Count the queries the callback runs against the dynamic configs table.
     */
    protected function countQueries(callable $callback): int
    {
        $count = 0;

        DB::listen(function ($query) use (&$count) {
            if (str_contains($query->sql, 'dynamic_configs')) {
                $count++;
            }
        });

        $callback();

        return $count;
    }
}
