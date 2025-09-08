<?php

namespace Codeman\FattureInCloud;

use Codeman\FattureInCloud\Contracts\ApiServiceFactory as ApiServiceFactoryContract;
use Codeman\FattureInCloud\Contracts\OAuth2Manager as OAuth2ManagerContract;
use Codeman\FattureInCloud\Contracts\StateManager as StateManagerContract;
use Codeman\FattureInCloud\Contracts\TokenStorage as TokenStorageContract;
use Codeman\FattureInCloud\Services\CacheTokenStorage;
use Codeman\FattureInCloud\Services\FattureInCloudApiServiceFactory;
use Codeman\FattureInCloud\Services\OAuth2AuthorizationCodeManager;
use Codeman\FattureInCloud\Services\SessionStateManager;
use FattureInCloud\Configuration;
use FattureInCloud\HeaderSelector;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Session\Session;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FattureInCloudServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-fatture-in-cloud')
            ->hasConfigFile();
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
        $this->app->singleton(TokenStorageContract::class, function ($app) {
            return new CacheTokenStorage(
                $app->make(CacheRepository::class),
                $app->make(Encrypter::class)
            );
        });
    }

    private function registerStateManager(): void
    {
        $this->app->singleton(StateManagerContract::class, function ($app) {
            return new SessionStateManager($app->make(Session::class));
        });
    }

    private function registerOAuth2Manager(): void
    {
        $this->app->singleton(OAuth2ManagerContract::class, function ($app) {
            $config = $app->make(ConfigRepository::class);

            return new OAuth2AuthorizationCodeManager(
                $app->make(StateManagerContract::class),
                $config->get('fattureincloud-php-sdk.client_id'),
                $config->get('fattureincloud-php-sdk.client_secret'),
                $this->getRedirectUrl($config)
            );
        });
    }

    private function registerApiServiceFactory(): void
    {
        $this->app->singleton(ApiServiceFactoryContract::class, function ($app) {
            $config = $app->make(ConfigRepository::class);
            $configuration = new Configuration;

            $accessToken = $config->get('fattureincloud-php-sdk.access_token');
            if ($accessToken) {
                $configuration->setAccessToken($accessToken);
            } else {
                $tokenStorage = $app->make(TokenStorageContract::class);
                $storedToken = $tokenStorage->getAccessToken('default');
                if ($storedToken) {
                    $configuration->setAccessToken($storedToken);
                }
            }

            return new FattureInCloudApiServiceFactory(
                new HttpClient,
                $configuration,
                new HeaderSelector
            );
        });
    }

    private function registerMainService(): void
    {
        $this->app->singleton(FattureInCloudSdk::class, function ($app) {
            return new FattureInCloudSdk(
                $app->make(OAuth2ManagerContract::class),
                $app->make(TokenStorageContract::class),
                $app->make(ApiServiceFactoryContract::class)
            );
        });

        $this->app->alias(FattureInCloudSdk::class, 'fatture-in-cloud');
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
