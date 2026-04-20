<?php

namespace Oxhq\Cachelet\Request\Facades;

use Illuminate\Support\Facades\Facade;

class CacheletRequest extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'cachelet.request';
    }
}
