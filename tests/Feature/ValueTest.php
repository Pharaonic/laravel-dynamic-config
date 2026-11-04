<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Pharaonic\Laravel\DynamicConfig\Exceptions\UnsupportedValueException;

it('keeps the value type', function (mixed $value) {
    Config::dynamic('branch-12')->set('app.value', $value);

    expect(Config::dynamic('branch-12')->get('app.value', 'missing'))->toBe($value);

    Config::dynamic('branch-12')->load();

    expect(config('app.value'))->toBe($value);
})->with([
    'null' => [null],
    'true' => [true],
    'false' => [false],
    'integer' => [42],
    'negative integer' => [-7],
    'zero' => [0],
    'float' => [1.5],
    'whole float' => [2.0],
    'string' => ['branch@example.com'],
    'empty string' => [''],
    'numeric string' => ['42'],
    'unicode string' => ['فرع ١٢'],
    'list' => [['a', 'b', 'c']],
    'associative array' => [['domain' => 'example.com', 'port' => 587]],
    'nested array' => [['smtp' => ['host' => 'smtp.test', 'ports' => [25, 587], 'tls' => true, 'auth' => null]]],
    'empty array' => [[]],
]);

it('rejects a value it cannot store', function (mixed $value, string $type) {
    expect(fn () => Config::dynamic('branch-12')->set('mail.secret', $value))
        ->toThrow(UnsupportedValueException::class, "The dynamic config [mail.secret] of the [branch-12] scope cannot store a value of type [{$type}]");

    expect(DB::table('dynamic_configs')->count())->toBe(0);
})->with([
    'closure' => [fn () => fn () => 'secret', 'Closure'],
    'object' => [fn () => new ArrayObject(['secret']), 'ArrayObject'],
    'plain object' => [fn () => new stdClass, 'stdClass'],
    'resource' => [fn () => STDIN, 'resource (stream)'],
    'nan' => [fn () => NAN, 'non-finite float'],
    'infinity' => [fn () => INF, 'non-finite float'],
    'invalid UTF-8' => [fn () => "\xB1\x31", 'non-UTF-8 string'],
]);

it('names the nested key of an unsupported value', function () {
    Config::dynamic()->set('mail', ['from' => ['address' => new stdClass]]);
})->throws(UnsupportedValueException::class, 'The dynamic config [mail.from.address] of the global scope cannot store a value of type [stdClass]');

it('does not put the value in the exception message', function () {
    try {
        Config::dynamic()->set('services.stripe', ['secret' => 'sk_live_abc', 'nan' => NAN]);
    } catch (UnsupportedValueException $exception) {
        expect($exception)->toBeInstanceOf(InvalidArgumentException::class)
            ->and($exception->getMessage())->not->toContain('sk_live_abc');

        return;
    }

    test()->fail('No exception was thrown.');
});
