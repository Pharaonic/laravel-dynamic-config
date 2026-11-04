<?php

declare(strict_types=1);

namespace Pharaonic\Laravel\DynamicConfig\Exceptions;

use InvalidArgumentException;

/**
 * Thrown when a value cannot be stored as dynamic config. The message names
 * the key, the identifier and the type, never the value itself.
 */
final class UnsupportedValueException extends InvalidArgumentException
{
    public function __construct(string $key, string $type, ?string $identifier)
    {
        parent::__construct(sprintf(
            'The dynamic config [%s] of the %s scope cannot store a value of type [%s]; only null, bool, int, finite float, UTF-8 string and arrays of them are supported.',
            $key,
            $identifier === null ? 'global' : "[{$identifier}]",
            $type,
        ));
    }
}
