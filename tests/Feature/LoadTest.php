<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;

beforeEach(function () {
    config(['mail.from' => ['address' => 'default@example.com', 'name' => 'My App']]);
});

it('merges the global scope into the config repository', function () {
    Config::dynamic()->set('mail.from.address', 'global@example.com');

    expect(config('mail.from.address'))->toBe('default@example.com');

    Config::dynamic()->load();

    expect(config('mail.from.address'))->toBe('global@example.com')
        ->and(Config::get('mail.from.address'))->toBe('global@example.com');
});

it('loads only the global scope', function () {
    Config::dynamic()->set('app.global', true);
    Config::dynamic('branch-12')->set('app.branch', true);

    Config::dynamic()->load();

    expect(config('app.global'))->toBeTrue()
        ->and(config('app.branch'))->toBeNull();
});

it('loads only the named scope', function () {
    Config::dynamic()->set('mail.from.address', 'global@example.com');
    Config::dynamic('company-1')->set('app.company', true);
    Config::dynamic('branch-12')->set('app.branch', true);

    Config::dynamic('branch-12')->load();

    expect(config('app.branch'))->toBeTrue()
        ->and(config('app.company'))->toBeNull()
        ->and(config('mail.from.address'))->toBe('default@example.com');
});

it('lets the later load win', function (array $order, string $expected) {
    Config::dynamic()->set('mail.from.address', 'global@example.com');
    Config::dynamic('company-1')->set('mail.from.address', 'company@example.com');
    Config::dynamic('branch-12')->set('mail.from.address', 'branch@example.com');

    foreach ($order as $identifier) {
        Config::dynamic($identifier)->load();
    }

    expect(config('mail.from.address'))->toBe($expected);
})->with([
    'global, then branch' => [[null, 'branch-12'], 'branch@example.com'],
    'branch, then global' => [['branch-12', null], 'global@example.com'],
    'global, company, branch' => [[null, 'company-1', 'branch-12'], 'branch@example.com'],
    'branch, company' => [['branch-12', 'company-1'], 'company@example.com'],
]);

it('keeps values a later scope does not override', function () {
    Config::dynamic()->set('mail.from.name', 'Global');
    Config::dynamic('branch-12')->set('mail.from.address', 'branch@example.com');

    Config::dynamic()->load();
    Config::dynamic('branch-12')->load();

    expect(config('mail.from'))->toBe(['address' => 'branch@example.com', 'name' => 'Global']);
});

it('overrides a nested key without touching its siblings', function () {
    Config::dynamic('branch-12')->set('mail.from.address', 'branch@example.com');

    Config::dynamic('branch-12')->load();

    expect(config('mail.from.address'))->toBe('branch@example.com')
        ->and(config('mail.from.name'))->toBe('My App');
});

it('creates keys the config files do not have', function () {
    Config::dynamic()->set('services.mailgun.domain', 'example.com');

    Config::dynamic()->load();

    expect(config('services.mailgun.domain'))->toBe('example.com');
});

it('merges an associative array recursively', function () {
    config(['services.mailgun' => ['domain' => 'base.com', 'secret' => 'base-secret', 'options' => ['timeout' => 5, 'retries' => 1]]]);
    Config::dynamic()->set('services.mailgun', ['domain' => 'dynamic.com', 'options' => ['timeout' => 10]]);

    Config::dynamic()->load();

    expect(config('services.mailgun'))->toBe([
        'domain' => 'dynamic.com',
        'secret' => 'base-secret',
        'options' => ['timeout' => 10, 'retries' => 1],
    ]);
});

it('replaces a list instead of appending to it', function () {
    config(['app.hosts' => ['a.test', 'b.test', 'c.test']]);
    config(['app.limits' => ['ips' => ['1.1.1.1', '2.2.2.2'], 'rate' => 60]]);
    Config::dynamic()->set('app.hosts', ['z.test']);
    Config::dynamic()->set('app.limits', ['ips' => ['9.9.9.9']]);

    Config::dynamic()->load();

    expect(config('app.hosts'))->toBe(['z.test'])
        ->and(config('app.limits'))->toBe(['ips' => ['9.9.9.9'], 'rate' => 60]);
});

it('replaces the current value with an empty array', function () {
    Config::dynamic()->set('mail.from', []);

    Config::dynamic()->load();

    expect(config('mail.from'))->toBe([]);
});

it('replaces a scalar with an array and an array with a scalar', function () {
    config(['app.flag' => true]);
    Config::dynamic()->set('app.flag', ['enabled' => true]);
    Config::dynamic()->set('mail.from', 'nobody');

    Config::dynamic()->load();

    expect(config('app.flag'))->toBe(['enabled' => true])
        ->and(config('mail.from'))->toBe('nobody');
});

it('applies a parent key before its children', function () {
    Config::dynamic()->set('mail.from.address', 'child@example.com');
    Config::dynamic()->set('mail', ['from' => ['address' => 'parent@example.com']]);

    Config::dynamic()->load();

    expect(config('mail.from'))->toBe(['address' => 'child@example.com', 'name' => 'My App']);
});

it('does not change the loaded config when a value is set or forgotten', function () {
    Config::dynamic()->set('mail.from.address', 'global@example.com');
    Config::dynamic()->load();

    Config::dynamic()->set('mail.from.address', 'changed@example.com');
    Config::dynamic('branch-12')->set('mail.from.address', 'branch@example.com');

    expect(config('mail.from.address'))->toBe('global@example.com');

    Config::dynamic()->forget('mail.from.address');

    expect(config('mail.from.address'))->toBe('global@example.com');

    Config::dynamic()->load();

    expect(config('mail.from.address'))->toBe('global@example.com');
});

it('keeps the base value after the override is forgotten', function () {
    Config::dynamic('branch-12')->set('mail.from.address', 'branch@example.com');
    Config::dynamic('branch-12')->forget('mail.from.address');

    Config::dynamic('branch-12')->load();

    expect(config('mail.from.address'))->toBe('default@example.com');
});

it('loads an empty scope without changing anything', function () {
    $before = config()->all();

    Config::dynamic('branch-12')->load();

    expect(config()->all())->toBe($before);
});

it('loads into the application\'s current config repository', function () {
    Config::dynamic()->set('app.foo', 'bar');
    $original = $this->app['config'];

    // Octane gives every request its own copy of the repository this way.
    $this->app->instance('config', $copy = clone $original);
    Config::clearResolvedInstances();

    Config::dynamic()->load();

    expect($copy->get('app.foo'))->toBe('bar')
        ->and($original->get('app.foo'))->toBeNull();
});

it('never writes the config cache file', function () {
    Config::dynamic()->set('app.foo', 'bar');
    Config::dynamic()->load();
    Config::dynamic()->forget('app.foo');

    expect(file_exists($this->app->getCachedConfigPath()))->toBeFalse();
});
