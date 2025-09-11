<?php

use Codeman\FattureInCloud\Contracts\ApiServiceFactory;
use Codeman\FattureInCloud\Contracts\OAuth2Manager;
use Codeman\FattureInCloud\Contracts\StateManager;
use Codeman\FattureInCloud\Contracts\TokenStorage;
use Codeman\FattureInCloud\FattureInCloudServiceProvider;
use Codeman\FattureInCloud\FattureInCloudSdk;
use Codeman\FattureInCloud\Services\CacheTokenStorage;
use Codeman\FattureInCloud\Services\FattureInCloudApiServiceFactory;
use Codeman\FattureInCloud\Services\OAuth2AuthorizationCodeManager;
use Codeman\FattureInCloud\Services\SessionStateManager;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Session\Session;

describe('FattureInCloudServiceProvider', function () {
    beforeEach(function () {
        $this->provider = new FattureInCloudServiceProvider($this->app);
    });

    describe('package configuration', function () {
        test('configures package correctly', function () {
            $package = Mockery::mock(\Spatie\LaravelPackageTools\Package::class);
            
            $package->shouldReceive('name')
                ->once()
                ->with('laravel-fatture-in-cloud')
                ->andReturnSelf();
            
            $package->shouldReceive('hasConfigFile')
                ->once()
                ->andReturnSelf();
                
            $package->shouldReceive('hasRoute')
                ->once()
                ->with('web')
                ->andReturnSelf();

            $this->provider->configurePackage($package);
        });
    });

    describe('service registration', function () {
        beforeEach(function () {
            // Configure test environment
            config([
                'fatture-in-cloud.client_id' => 'test-client-id',
                'fatture-in-cloud.client_secret' => 'test-client-secret',
                'fatture-in-cloud.redirect_url' => 'https://test.com/callback',
            ]);

            $this->provider->packageRegistered();
        });

        test('registers TokenStorage as singleton', function () {
            $instance1 = $this->app->make(TokenStorage::class);
            $instance2 = $this->app->make(TokenStorage::class);

            expect($instance1)->toBeInstanceOf(CacheTokenStorage::class);
            expect($instance1)->toBe($instance2); // Same instance (singleton)
        });

        test('registers StateManager as singleton', function () {
            $instance1 = $this->app->make(StateManager::class);
            $instance2 = $this->app->make(StateManager::class);

            expect($instance1)->toBeInstanceOf(SessionStateManager::class);
            expect($instance1)->toBe($instance2); // Same instance (singleton)
        });

        test('registers OAuth2Manager as singleton', function () {
            $instance1 = $this->app->make(OAuth2Manager::class);
            $instance2 = $this->app->make(OAuth2Manager::class);

            expect($instance1)->toBeInstanceOf(OAuth2AuthorizationCodeManager::class);
            expect($instance1)->toBe($instance2); // Same instance (singleton)
        });

        test('registers ApiServiceFactory as singleton', function () {
            $instance1 = $this->app->make(ApiServiceFactory::class);
            $instance2 = $this->app->make(ApiServiceFactory::class);

            expect($instance1)->toBeInstanceOf(FattureInCloudApiServiceFactory::class);
            expect($instance1)->toBe($instance2); // Same instance (singleton)
        });

        test('registers main FattureInCloudSdk as singleton', function () {
            $instance1 = $this->app->make(FattureInCloudSdk::class);
            $instance2 = $this->app->make(FattureInCloudSdk::class);

            expect($instance1)->toBeInstanceOf(FattureInCloudSdk::class);
            expect($instance1)->toBe($instance2); // Same instance (singleton)
        });

        test('creates alias for main service', function () {
            $sdkInstance = $this->app->make(FattureInCloudSdk::class);
            $aliasInstance = $this->app->make('fatture-in-cloud');

            expect($aliasInstance)->toBe($sdkInstance);
        });
    });

    describe('TokenStorage registration', function () {
        test('creates CacheTokenStorage with correct dependencies', function () {
            $this->provider->packageRegistered();

            $tokenStorage = $this->app->make(TokenStorage::class);

            expect($tokenStorage)->toBeInstanceOf(CacheTokenStorage::class);
        });

        test('resolves CacheRepository and Encrypter dependencies', function () {
            // Mock dependencies to verify they are passed correctly
            $mockCache = Mockery::mock(CacheRepository::class);
            $mockEncrypter = Mockery::mock(Encrypter::class);

            $this->app->instance(CacheRepository::class, $mockCache);
            $this->app->instance(Encrypter::class, $mockEncrypter);

            $this->provider->packageRegistered();

            $tokenStorage = $this->app->make(TokenStorage::class);

            expect($tokenStorage)->toBeInstanceOf(CacheTokenStorage::class);
        });
    });

    describe('StateManager registration', function () {
        test('creates SessionStateManager with Session dependency', function () {
            $this->provider->packageRegistered();

            $stateManager = $this->app->make(StateManager::class);

            expect($stateManager)->toBeInstanceOf(SessionStateManager::class);
        });

        test('resolves Session dependency', function () {
            $mockSession = Mockery::mock(Session::class);
            $this->app->instance(Session::class, $mockSession);

            $this->provider->packageRegistered();

            $stateManager = $this->app->make(StateManager::class);

            expect($stateManager)->toBeInstanceOf(SessionStateManager::class);
        });
    });

    describe('OAuth2Manager registration', function () {
        test('creates OAuth2AuthorizationCodeManager with correct configuration', function () {
            config([
                'fatture-in-cloud.client_id' => 'test-client',
                'fatture-in-cloud.client_secret' => 'test-secret',
                'fatture-in-cloud.redirect_url' => 'https://example.com/callback',
            ]);

            $this->provider->packageRegistered();

            $oauth2Manager = $this->app->make(OAuth2Manager::class);

            expect($oauth2Manager)->toBeInstanceOf(OAuth2AuthorizationCodeManager::class);
            expect($oauth2Manager->isInitialized())->toBeTrue();
        });

        test('uses route generation when redirect_url is not configured', function () {
            config([
                'fatture-in-cloud.client_id' => 'test-client',
                'fatture-in-cloud.client_secret' => 'test-secret',
                'fatture-in-cloud.redirect_url' => null,
            ]);

            $this->provider->packageRegistered();

            $oauth2Manager = $this->app->make(OAuth2Manager::class);

            expect($oauth2Manager)->toBeInstanceOf(OAuth2AuthorizationCodeManager::class);
            expect($oauth2Manager->isInitialized())->toBeTrue();
        });

        test('handles missing configuration gracefully', function () {
            config([
                'fatture-in-cloud.client_id' => null,
                'fatture-in-cloud.client_secret' => null,
                'fatture-in-cloud.redirect_url' => null,
            ]);

            $this->provider->packageRegistered();

            $oauth2Manager = $this->app->make(OAuth2Manager::class);

            expect($oauth2Manager)->toBeInstanceOf(OAuth2AuthorizationCodeManager::class);
            expect($oauth2Manager->isInitialized())->toBeFalse();
        });
    });

    describe('ApiServiceFactory registration', function () {
        test('creates FattureInCloudApiServiceFactory with Configuration', function () {
            $this->provider->packageRegistered();

            $factory = $this->app->make(ApiServiceFactory::class);

            expect($factory)->toBeInstanceOf(FattureInCloudApiServiceFactory::class);
        });

        test('uses access_token from config when available', function () {
            config(['fatture-in-cloud.access_token' => 'manual-access-token']);

            $this->provider->packageRegistered();

            $factory = $this->app->make(ApiServiceFactory::class);

            expect($factory)->toBeInstanceOf(FattureInCloudApiServiceFactory::class);
        });

        test('falls back to stored token when access_token not configured', function () {
            config(['fatture-in-cloud.access_token' => null]);

            // Mock TokenStorage to return a stored token
            $mockTokenStorage = Mockery::mock(TokenStorage::class);
            $mockTokenStorage->shouldReceive('getAccessToken')
                ->with('default')
                ->andReturn('stored-access-token');

            $this->app->instance(TokenStorage::class, $mockTokenStorage);

            $this->provider->packageRegistered();

            $factory = $this->app->make(ApiServiceFactory::class);

            expect($factory)->toBeInstanceOf(FattureInCloudApiServiceFactory::class);
        });

        test('works without any token configuration', function () {
            config(['fatture-in-cloud.access_token' => null]);

            // Mock TokenStorage to return no stored token
            $mockTokenStorage = Mockery::mock(TokenStorage::class);
            $mockTokenStorage->shouldReceive('getAccessToken')
                ->with('default')
                ->andReturn(null);

            $this->app->instance(TokenStorage::class, $mockTokenStorage);

            $this->provider->packageRegistered();

            $factory = $this->app->make(ApiServiceFactory::class);

            expect($factory)->toBeInstanceOf(FattureInCloudApiServiceFactory::class);
        });
    });

    describe('main service registration', function () {
        test('creates FattureInCloudSdk with all dependencies', function () {
            config([
                'fatture-in-cloud.client_id' => 'test-client',
                'fatture-in-cloud.client_secret' => 'test-secret',
                'fatture-in-cloud.redirect_url' => 'https://test.com/callback',
            ]);

            $this->provider->packageRegistered();

            $sdk = $this->app->make(FattureInCloudSdk::class);

            expect($sdk)->toBeInstanceOf(FattureInCloudSdk::class);
            expect($sdk->auth())->toBeInstanceOf(OAuth2Manager::class);
        });

        test('resolves dependencies through container', function () {
            $this->provider->packageRegistered();

            $sdk = $this->app->make(FattureInCloudSdk::class);
            $oauth2Manager = $this->app->make(OAuth2Manager::class);
            $tokenStorage = $this->app->make(TokenStorage::class);
            $apiFactory = $this->app->make(ApiServiceFactory::class);

            expect($sdk->auth())->toBe($oauth2Manager);
        });
    });

    describe('route registration', function () {
        test('registers callback route', function () {
            // The route is registered through the web routes file
            // We can test that the route exists
            expect(route('fatture-in-cloud.callback'))
                ->toContain('/fatture-in-cloud/callback');
        });
    });

    afterEach(function () {
        Mockery::close();
    });
});