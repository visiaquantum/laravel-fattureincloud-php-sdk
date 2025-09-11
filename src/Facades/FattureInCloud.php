<?php

namespace Codeman\FattureInCloud\Facades;

use Codeman\FattureInCloud\FattureInCloudSdk;
use Illuminate\Support\Facades\Facade;

/**
 * @see FattureInCloudSdk
 */
class FattureInCloud extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FattureInCloudSdk::class;
    }
}
