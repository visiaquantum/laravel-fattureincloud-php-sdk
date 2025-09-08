<?php

namespace Codeman\FattureInCloud\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Codeman\FattureInCloud\LaravelFattureInCloudPhpSdk
 */
class FattureInCloud extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Codeman\FattureInCloud\LaravelFattureInCloudPhpSdk::class;
    }
}
