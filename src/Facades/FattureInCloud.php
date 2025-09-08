<?php

namespace Codeman\FattureInCloud\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Codeman\FattureInCloud\FattureInCloudSdk
 */
class FattureInCloud extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Codeman\FattureInCloud\FattureInCloudSdk::class;
    }
}
