<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

it('queries a scope once and then reads it from the cache', function () {
    Config::dynamic('branch-12')->set('app.foo', 'bar');

    $first = $this->countQueries(fn () => Config::dynamic('branch-12')->all());
    $later = $this->countQueries(function () {
        Config::dynamic('branch-12')->get('app.foo');
        Config::dynamic('branch-12')->has('app.foo');
        Config::dynamic('branch-12')->load();
    });

    expect($first)->toBe(1)
        ->and($later)->toBe(0)
        ->and(config('app.foo'))->toBe('bar');
});

it('does not query again for an empty scope', function () {
    Config::dynamic('branch-12')->all();

    expect($this->countQueries(fn () => Config::dynamic('branch-12')->load()))->toBe(0);
});

it('keeps reading the cache after a direct database change', function () {
    Config::dynamic()->set('app.foo', 'bar');
    Config::dynamic()->all();

    DB::table('dynamic_configs')->update(['value' => '"changed"']);

    expect(Config::dynamic()->get('app.foo'))->toBe('bar');
});

it('invalidates only the written scope', function (string $operation) {
    Config::dynamic()->set('app.foo', 'global');
    Config::dynamic('branch-12')->set('app.foo', 'branch');
    Config::dynamic()->all();
    Config::dynamic('branch-12')->all();

    $operation === 'set'
        ? Config::dynamic('branch-12')->set('app.foo', 'changed')
        : Config::dynamic('branch-12')->forget('app.foo');

    expect($this->countQueries(fn () => Config::dynamic()->all()))->toBe(0)
        ->and($this->countQueries(fn () => Config::dynamic('branch-12')->all()))->toBe(1)
        ->and(Config::dynamic('branch-12')->get('app.foo'))->toBe($operation === 'set' ? 'changed' : null);
})->with(['set', 'forget']);

it('keeps the global scope apart from an identifier named like it', function (string $identifier) {
    Config::dynamic()->set('app.foo', 'global');
    Config::dynamic($identifier)->set('app.foo', 'named');

    expect(Config::dynamic()->get('app.foo'))->toBe('global')
        ->and(Config::dynamic($identifier)->get('app.foo'))->toBe('named');
})->with(['global', 'null', '0', 'pharaonic:dynamic-config']);

it('only touches its own cache entries', function () {
    Cache::put('unrelated', 'kept');

    Config::dynamic()->set('app.foo', 'bar');
    Config::dynamic()->all();
    Config::dynamic()->forget('app.foo');

    expect(Cache::get('unrelated'))->toBe('kept');
});

it('uses the configured cache store', function () {
    config(['pharaonic.dynamic-config.cache.store' => 'secondary']);

    Config::dynamic()->set('app.foo', 'bar');
    Config::dynamic()->all();

    Cache::store('array')->flush();

    expect($this->countQueries(fn () => Config::dynamic()->all()))->toBe(0);

    Cache::store('secondary')->flush();

    expect($this->countQueries(fn () => Config::dynamic()->all()))->toBe(1);
});

it('expires the cache after the configured ttl', function () {
    config(['pharaonic.dynamic-config.cache.ttl' => 60]);

    Config::dynamic()->set('app.foo', 'bar');
    Config::dynamic()->all();

    $this->travel(59)->seconds();

    expect($this->countQueries(fn () => Config::dynamic()->all()))->toBe(0);

    $this->travel(2)->seconds();

    expect($this->countQueries(fn () => Config::dynamic()->all()))->toBe(1);
});

it('queries every time when the cache is disabled', function () {
    config(['pharaonic.dynamic-config.cache.enabled' => false]);

    Config::dynamic()->set('app.foo', 'bar');

    $queries = $this->countQueries(function () {
        Config::dynamic()->all();
        Config::dynamic()->get('app.foo');
    });

    DB::table('dynamic_configs')->update(['value' => '"changed"']);

    expect($queries)->toBe(2)
        ->and(Config::dynamic()->get('app.foo'))->toBe('changed')
        ->and(Cache::getStore()->get('pharaonic:dynamic-config:'.sha1((string) json_encode([null, 'dynamic_configs', null]))))->toBeNull();
});
