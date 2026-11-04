<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

dataset('scopes', [
    'global' => [null],
    'named' => ['branch-12'],
]);

it('treats dynamic() and dynamic(null) as the global scope', function () {
    Config::dynamic()->set('app.foo', 'one');

    expect(Config::dynamic(null)->get('app.foo'))->toBe('one')
        ->and(Config::dynamic(null))->toEqual(Config::dynamic())
        ->and(DB::table('dynamic_configs')->value('identifier'))->toBeNull();
});

it('stores, reads, lists and forgets a value', function (?string $identifier) {
    $scope = Config::dynamic($identifier);

    expect($scope->has('mail.from.address'))->toBeFalse()
        ->and($scope->get('mail.from.address'))->toBeNull()
        ->and($scope->all())->toBe([]);

    $scope->set('mail.from.address', 'branch@example.com');
    $scope->set('services.mailgun.domain', 'branch.example.com');

    expect($scope->has('mail.from.address'))->toBeTrue()
        ->and($scope->get('mail.from.address'))->toBe('branch@example.com')
        ->and($scope->all())->toBe([
            'mail.from.address' => 'branch@example.com',
            'services.mailgun.domain' => 'branch.example.com',
        ]);

    $scope->forget('mail.from.address');

    expect($scope->has('mail.from.address'))->toBeFalse()
        ->and($scope->all())->toBe(['services.mailgun.domain' => 'branch.example.com'])
        ->and(DB::table('dynamic_configs')->count())->toBe(1);
})->with('scopes');

it('returns the default for a missing key', function (?string $identifier) {
    expect(Config::dynamic($identifier)->get('mail.from.address', 'fallback@example.com'))->toBe('fallback@example.com')
        ->and(Config::dynamic($identifier)->get('mail.from.address', fn () => 'lazy@example.com'))->toBe('lazy@example.com');
})->with('scopes');

it('does not fall back to the config files or another scope', function () {
    config(['mail.from.address' => 'default@example.com']);
    Config::dynamic()->set('mail.from.address', 'global@example.com');

    expect(Config::dynamic('branch-12')->get('mail.from.address'))->toBeNull()
        ->and(Config::dynamic('branch-12')->has('mail.from.address'))->toBeFalse();
});

it('matches stored keys exactly', function () {
    Config::dynamic()->set('mail.from', ['address' => 'global@example.com']);

    expect(Config::dynamic()->has('mail.from'))->toBeTrue()
        ->and(Config::dynamic()->has('mail.from.address'))->toBeFalse()
        ->and(Config::dynamic()->has('mail'))->toBeFalse();
});

it('reports a stored null as present', function (?string $identifier) {
    Config::dynamic($identifier)->set('app.foo', null);

    expect(Config::dynamic($identifier)->has('app.foo'))->toBeTrue()
        ->and(Config::dynamic($identifier)->get('app.foo', 'default'))->toBeNull();
})->with('scopes');

it('keeps one row per key when a value is set again', function (?string $identifier) {
    Config::dynamic($identifier)->set('app.foo', 'one');
    Config::dynamic($identifier)->set('app.foo', 'two');
    Config::dynamic($identifier)->set('app.foo', 'two');

    expect(Config::dynamic($identifier)->get('app.foo'))->toBe('two')
        ->and(DB::table('dynamic_configs')->where('key', 'app.foo')->count())->toBe(1);
})->with('scopes');

it('removes an older duplicate global row left by a concurrent insert', function () {
    DB::table('dynamic_configs')->insert(['identifier' => null, 'key' => 'app.foo', 'value' => '"stale"']);
    DB::table('dynamic_configs')->insert(['identifier' => null, 'key' => 'app.foo', 'value' => '"newer"']);

    expect(Config::dynamic()->get('app.foo'))->toBe('newer');

    Config::dynamic()->set('app.foo', 'two');

    expect(Config::dynamic()->get('app.foo'))->toBe('two')
        ->and(DB::table('dynamic_configs')->where('key', 'app.foo')->count())->toBe(2);

    Config::dynamic()->forget('app.foo');
    Config::dynamic()->set('app.foo', 'three');

    expect(DB::table('dynamic_configs')->where('key', 'app.foo')->pluck('value')->all())->toBe(['"three"']);
});

it('keeps scopes independent', function () {
    Config::dynamic('a')->set('app.foo', 1);
    Config::dynamic('b')->set('app.foo', 2);
    Config::dynamic()->set('app.foo', 3);

    expect(Config::dynamic('a')->get('app.foo'))->toBe(1)
        ->and(Config::dynamic('b')->get('app.foo'))->toBe(2)
        ->and(Config::dynamic()->get('app.foo'))->toBe(3)
        ->and(DB::table('dynamic_configs')->count())->toBe(3);

    Config::dynamic('a')->forget('app.foo');

    expect(Config::dynamic('a')->has('app.foo'))->toBeFalse()
        ->and(Config::dynamic('b')->get('app.foo'))->toBe(2)
        ->and(Config::dynamic()->get('app.foo'))->toBe(3);
});

it('lists values in key order', function () {
    Config::dynamic()->set('services.mailgun.domain', 'example.com');
    Config::dynamic()->set('app.name', 'App');
    Config::dynamic()->set('mail.from.address', 'global@example.com');

    expect(array_keys(Config::dynamic()->all()))->toBe(['app.name', 'mail.from.address', 'services.mailgun.domain']);
});

it('stores the value as JSON', function () {
    Config::dynamic('branch-12')->set('mail.from.address', 'branch@example.com');

    $row = DB::table('dynamic_configs')->first();

    expect($row->identifier)->toBe('branch-12')
        ->and($row->key)->toBe('mail.from.address')
        ->and($row->value)->toBe('"branch@example.com"')
        ->and($row->created_at)->not->toBeNull()
        ->and($row->updated_at)->not->toBeNull();
});

it('forgets a missing key without error', function () {
    Config::dynamic('branch-12')->forget('app.missing');

    expect(Config::dynamic('branch-12')->all())->toBe([]);
});

it('uses the configured table and connection', function () {
    config(['database.connections.other' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
    config(['pharaonic.dynamic-config.connection' => 'other', 'pharaonic.dynamic-config.table' => 'settings']);

    DB::connection('other')->getSchemaBuilder()->create('settings', function ($table) {
        $table->id();
        $table->string('identifier')->nullable();
        $table->string('key');
        $table->text('value');
        $table->timestamps();
        $table->unique(['identifier', 'key']);
    });

    Config::dynamic('branch-12')->set('app.foo', 'bar');
    Config::dynamic()->set('app.foo', 'baz');

    expect(DB::connection('other')->table('settings')->count())->toBe(2)
        ->and(DB::table('dynamic_configs')->count())->toBe(0)
        ->and(Config::dynamic('branch-12')->get('app.foo'))->toBe('bar');
});

it('rejects an empty identifier', function () {
    Config::dynamic('');
})->throws(InvalidArgumentException::class, 'identifier must be null or a non-empty string');

it('rejects an array of identifiers', function () {
    Config::dynamic(['company-1', 'branch-12']);
})->throws(TypeError::class);

it('rejects an invalid key', function (string $key) {
    Config::dynamic()->set($key, 'value');
})->with(['', '.', 'mail.', '.mail', 'mail..from'])->throws(InvalidArgumentException::class);
