<?php

namespace Codeman\LaravelFattureInCloudPhpSdk;

use Codeman\LaravelFattureInCloudPhpSdk\Commands\FattureInCloudCommand;
use Illuminate\Config\Repository as ConfigRepository;
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

    public function packageRegistered(): void
    {
        $this->app->singleton(LaravelFattureInCloudPhpSdk::class, function ($app) {
            return new LaravelFattureInCloudPhpSdk($app->make(ConfigRepository::class));
        });

        $this->app->alias(LaravelFattureInCloudPhpSdk::class, 'fatture-in-cloud');
    }
}
