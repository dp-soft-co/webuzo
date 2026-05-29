<?php

declare(strict_types=1);

namespace Webuzo\Facades;

use Illuminate\Support\Facades\Facade;
use Webuzo\WebuzoManager;

class Webuzo extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return WebuzoManager::class;
    }
}
