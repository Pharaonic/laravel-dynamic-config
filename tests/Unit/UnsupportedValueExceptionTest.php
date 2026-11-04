<?php

declare(strict_types=1);

use Pharaonic\Laravel\DynamicConfig\Exceptions\UnsupportedValueException;

it('names the key, scope and type', function (?string $identifier, string $scope) {
    $exception = new UnsupportedValueException('mail.from', 'Closure', $identifier);

    expect($exception)->toBeInstanceOf(InvalidArgumentException::class)
        ->and($exception->getMessage())->toBe(
            "The dynamic config [mail.from] of the {$scope} scope cannot store a value of type [Closure]; only null, bool, int, finite float, UTF-8 string and arrays of them are supported."
        );
})->with([
    'global' => [null, 'global'],
    'named' => ['branch-12', '[branch-12]'],
]);
