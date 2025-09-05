<?php

namespace Codeman\LaravelFattureInCloudPhpSdk;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Codeman\LaravelFattureInCloudPhpSdk\Commands\LaravelFattureInCloudPhpSdkCommand;

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
            ->hasCommand(LaravelFattureInCloudPhpSdkCommand::class);
    }
}
