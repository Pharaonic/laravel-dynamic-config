<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Pharaonic\Laravel\DynamicConfig\DynamicConfig;
use Pharaonic\Laravel\DynamicConfig\Exceptions\UnsupportedValueException;
use Pharaonic\Laravel\DynamicConfig\Tests\Fixtures\Branch;
use Pharaonic\Laravel\DynamicConfig\Tests\Fixtures\Company;

afterEach(function () {
    Relation::morphMap([], false);
});

it('scopes the values to a saved model', function () {
    $branch = (new Branch)->newFromBuilder(['id' => 12]);

    expect(Config::dynamic($branch))->toBeInstanceOf(DynamicConfig::class);

    Config::dynamic($branch)->set('mail.from.address', 'branch@example.com');

    expect(DB::table('dynamic_configs')->value('identifier'))->toBe(Branch::class.':12')
        ->and(Config::dynamic($branch)->get('mail.from.address'))->toBe('branch@example.com')
        ->and(Config::dynamic()->has('mail.from.address'))->toBeFalse();

    Config::dynamic($branch)->load();

    expect(config('mail.from.address'))->toBe('branch@example.com');
});

it('uses the morph map alias of the model', function () {
    Relation::morphMap(['branch' => Branch::class]);

    Config::dynamic((new Branch)->newFromBuilder(['id' => 12]))->set('app.foo', 'bar');

    expect(DB::table('dynamic_configs')->value('identifier'))->toBe('branch:12')
        ->and(Config::dynamic('branch:12')->get('app.foo'))->toBe('bar');
});

it('supports string keys', function () {
    Config::dynamic((new Company)->newFromBuilder(['id' => 'acme']))->set('app.foo', 'bar');

    expect(Config::dynamic(Company::class.':acme')->get('app.foo'))->toBe('bar');
});

it('keeps models of different types with the same key apart', function () {
    Config::dynamic((new Branch)->newFromBuilder(['id' => 1]))->set('app.foo', 'branch');
    Config::dynamic((new Company)->newFromBuilder(['id' => '1']))->set('app.foo', 'company');

    expect(Config::dynamic((new Branch)->newFromBuilder(['id' => 1]))->get('app.foo'))->toBe('branch')
        ->and(Config::dynamic((new Company)->newFromBuilder(['id' => '1']))->get('app.foo'))->toBe('company');
});

it('rejects a model that is not saved', function () {
    Config::dynamic((new Branch)->forceFill(['id' => 12]));
})->throws(InvalidArgumentException::class, 'model ['.Branch::class.'] must be saved');

it('rejects a model without a key', function () {
    Config::dynamic((new Branch)->newFromBuilder([]));
})->throws(InvalidArgumentException::class, 'model ['.Branch::class.'] must be saved');

it('names the model scope in an unsupported value error', function () {
    Relation::morphMap(['branch' => Branch::class]);

    Config::dynamic((new Branch)->newFromBuilder(['id' => 12]))->set('app.foo', new stdClass);
})->throws(UnsupportedValueException::class, 'of the [branch:12] scope');
