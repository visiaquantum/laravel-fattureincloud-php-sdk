<?php

namespace Codeman\LaravelFattureInCloudPhpSdk;

use Codeman\LaravelFattureInCloudPhpSdk\Commands\FattureInCloudCommand;
use Codeman\LaravelFattureInCloudPhpSdk\Contracts\ApiServiceFactoryInterface;
use Codeman\LaravelFattureInCloudPhpSdk\Contracts\OAuth2ManagerInterface;
use Codeman\LaravelFattureInCloudPhpSdk\Contracts\StateManagerInterface;
use Codeman\LaravelFattureInCloudPhpSdk\Contracts\TokenStorageInterface;
use Codeman\LaravelFattureInCloudPhpSdk\Services\ApiServiceFactory;
use Codeman\LaravelFattureInCloudPhpSdk\Services\OAuth2Manager;
use Codeman\LaravelFattureInCloudPhpSdk\Services\StateManager;
use Codeman\LaravelFattureInCloudPhpSdk\Services\TokenStorage;
use FattureInCloud\Configuration;
use FattureInCloud\HeaderSelector;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Session\Session;
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
        $this->registerTokenStorage();
        $this->registerStateManager();
        $this->registerOAuth2Manager();
        $this->registerApiServiceFactory();
        $this->registerMainService();
    }

    private function registerTokenStorage(): void
    {
        $this->app->singleton(TokenStorageInterface::class, function ($app) {
            return new TokenStorage(
                $app->make(CacheRepository::class),
                $app->make(Encrypter::class)
            );
        });
    }

    private function registerStateManager(): void
    {
        $this->app->singleton(StateManagerInterface::class, function ($app) {
            return new StateManager($app->make(Session::class));
        });
    }

    private function registerOAuth2Manager(): void
    {
        $this->app->singleton(OAuth2ManagerInterface::class, function ($app) {
            $config = $app->make(ConfigRepository::class);

            return new OAuth2Manager(
                $app->make(StateManagerInterface::class),
                $config->get('fattureincloud-php-sdk.client_id'),
                $config->get('fattureincloud-php-sdk.client_secret'),
                $this->getRedirectUrl($config)
            );
        });
    }

    private function registerApiServiceFactory(): void
    {
        $this->app->singleton(ApiServiceFactoryInterface::class, function ($app) {
            $config = $app->make(ConfigRepository::class);
            $configuration = new Configuration;

            $accessToken = $config->get('fattureincloud-php-sdk.access_token');
            if ($accessToken) {
                $configuration->setAccessToken($accessToken);
            } else {
                $tokenStorage = $app->make(TokenStorageInterface::class);
                $storedToken = $tokenStorage->getAccessToken('default');
                if ($storedToken) {
                    $configuration->setAccessToken($storedToken);
                }
            }

            return new ApiServiceFactory(
                new HttpClient,
                $configuration,
                new HeaderSelector
            );
        });
    }

    private function registerMainService(): void
    {
        $this->app->singleton(LaravelFattureInCloudPhpSdk::class, function ($app) {
            return new LaravelFattureInCloudPhpSdk(
                $app->make(OAuth2ManagerInterface::class),
                $app->make(TokenStorageInterface::class),
                $app->make(ApiServiceFactoryInterface::class)
            );
        });

        $this->app->alias(LaravelFattureInCloudPhpSdk::class, 'fatture-in-cloud');
    }

    private function getRedirectUrl(ConfigRepository $config): string
    {
        $redirectUrl = $config->get('fattureincloud-php-sdk.redirect_url');

        if ($redirectUrl) {
            return $redirectUrl;
        }

        return $config->get('app.url').'/fatture-in-cloud/callback';
    }
}
