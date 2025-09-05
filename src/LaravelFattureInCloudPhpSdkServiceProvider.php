<?php

namespace Codeman\LaravelFattureInCloudPhpSdk;

use Codeman\LaravelFattureInCloudPhpSdk\Commands\FattureInCloudCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelFattureInCloudPhpSdkServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-fattureincloud-php-sdk')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_fattureincloud_php_sdk_table')
            ->hasCommand(FattureInCloudCommand::class);
    }
}
