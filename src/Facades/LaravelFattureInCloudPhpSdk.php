<?php

namespace Codeman\LaravelFattureInCloudPhpSdk\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Codeman\LaravelFattureInCloudPhpSdk\LaravelFattureInCloudPhpSdk
 */
class LaravelFattureInCloudPhpSdk extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Codeman\LaravelFattureInCloudPhpSdk\LaravelFattureInCloudPhpSdk::class;
    }
}
