<?php

declare(strict_types=1);

namespace Pharaonic\Laravel\DynamicConfig\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

final class Company extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';
}
