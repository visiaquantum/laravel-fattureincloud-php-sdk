<?php

use Codeman\FattureInCloud\Contracts\ApiServiceFactory as ApiServiceFactoryContract;
use Codeman\FattureInCloud\Contracts\OAuth2Manager as OAuth2ManagerContract;
use Codeman\FattureInCloud\Contracts\StateManager as StateManagerContract;
use Codeman\FattureInCloud\Contracts\TokenStorage as TokenStorageContract;
use Codeman\FattureInCloud\Controllers\OAuth2CallbackController;
use Codeman\FattureInCloud\Facades\FattureInCloud;
use Codeman\FattureInCloud\FattureInCloudServiceProvider;
use Codeman\FattureInCloud\FattureInCloudSdk;
use Codeman\FattureInCloud\Services\CacheTokenStorage;
use Codeman\FattureInCloud\Services\FattureInCloudApiServiceFactory;
use Codeman\FattureInCloud\Services\OAuth2AuthorizationCodeManager;
use Codeman\FattureInCloud\Services\SessionStateManager;
use FattureInCloud\Api\ClientsApi;
use FattureInCloud\Api\CompaniesApi;
use FattureInCloud\Api\InfoApi;
use FattureInCloud\Api\IssuedDocumentsApi;
use FattureInCloud\Api\ProductsApi;
use FattureInCloud\Api\ReceiptsApi;
use FattureInCloud\Api\ReceivedDocumentsApi;
use FattureInCloud\Api\SuppliersApi;
use FattureInCloud\Api\TaxesApi;
use FattureInCloud\Api\UserApi;
use FattureInCloud\Api\SettingsApi;
use FattureInCloud\Api\ArchiveApi;
use FattureInCloud\Api\CashbookApi;
use FattureInCloud\Api\PriceListsApi;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Session\Store as Session;
use Illuminate\Support\Facades\Route;

describe('End-to-End Package Installation', function () {
    describe('Service Provider Registration', function () {
        it('automatically discovers service provider', function () {
            $providers = app()->getProviders(FattureInCloudServiceProvider::class);

            expect($providers)->not->toBeEmpty();
            expect(count($providers))->toBeGreaterThan(0);

            // Check that the provider class is registered
            $providerExists = false;
            foreach ($providers as $provider) {
                if ($provider instanceof FattureInCloudServiceProvider) {
                    $providerExists = true;
                    break;
                }
            }
            expect($providerExists)->toBeTrue();
        });

        it('registers all expected services in container', function () {
            // Check that all contracts are bound
            expect(app()->bound(OAuth2ManagerContract::class))->toBeTrue();
            expect(app()->bound(StateManagerContract::class))->toBeTrue();
            expect(app()->bound(TokenStorageContract::class))->toBeTrue();
            expect(app()->bound(ApiServiceFactoryContract::class))->toBeTrue();
            expect(app()->bound(FattureInCloudSdk::class))->toBeTrue();
            expect(app()->bound('fatture-in-cloud'))->toBeTrue();
        });

        it('registers services as singletons', function () {
            $oauth2Manager1 = app(OAuth2ManagerContract::class);
            $oauth2Manager2 = app(OAuth2ManagerContract::class);
            expect($oauth2Manager1)->toBe($oauth2Manager2);

            $tokenStorage1 = app(TokenStorageContract::class);
            $tokenStorage2 = app(TokenStorageContract::class);
            expect($tokenStorage1)->toBe($tokenStorage2);

            $apiFactory1 = app(ApiServiceFactoryContract::class);
            $apiFactory2 = app(ApiServiceFactoryContract::class);
            expect($apiFactory1)->toBe($apiFactory2);

            $sdk1 = app(FattureInCloudSdk::class);
            $sdk2 = app(FattureInCloudSdk::class);
            expect($sdk1)->toBe($sdk2);
        });

        it('creates proper service implementations', function () {
            expect(app(OAuth2ManagerContract::class))->toBeInstanceOf(OAuth2AuthorizationCodeManager::class);
            expect(app(StateManagerContract::class))->toBeInstanceOf(SessionStateManager::class);
            expect(app(TokenStorageContract::class))->toBeInstanceOf(CacheTokenStorage::class);
            expect(app(ApiServiceFactoryContract::class))->toBeInstanceOf(FattureInCloudApiServiceFactory::class);
            expect(app(FattureInCloudSdk::class))->toBeInstanceOf(FattureInCloudSdk::class);
        });

        it('injects proper dependencies into services', function () {
            // StateManager should have Session dependency
            $stateManager = app(StateManagerContract::class);
            expect($stateManager)->toBeInstanceOf(SessionStateManager::class);

            // TokenStorage should have Cache and Encrypter dependencies
            $tokenStorage = app(TokenStorageContract::class);
            expect($tokenStorage)->toBeInstanceOf(CacheTokenStorage::class);

            // ApiServiceFactory should have Configuration
            $apiFactory = app(ApiServiceFactoryContract::class);
            expect($apiFactory)->toBeInstanceOf(FattureInCloudApiServiceFactory::class);

            // FattureInCloudSdk should have all dependencies
            $sdk = app(FattureInCloudSdk::class);
            expect($sdk)->toBeInstanceOf(FattureInCloudSdk::class);
            expect($sdk->auth())->toBeInstanceOf(OAuth2ManagerContract::class);
        });
    });

    describe('Route Registration', function () {
        it('automatically registers OAuth2 callback route', function () {
            $routes = collect(Route::getRoutes()->getRoutes());
            $callbackRoute = $routes->first(function ($route) {
                return $route->getName() === 'fatture-in-cloud.callback';
            });

            expect($callbackRoute)->not->toBeNull();
            expect($callbackRoute->uri())->toBe('fatture-in-cloud/callback');
            expect($callbackRoute->methods())->toContain('GET');
        });

        it('callback route uses correct controller', function () {
            $routes = collect(Route::getRoutes()->getRoutes());
            $callbackRoute = $routes->first(function ($route) {
                return $route->getName() === 'fatture-in-cloud.callback';
            });

            $action = $callbackRoute->getAction();
            expect($action['controller'])->toBe(OAuth2CallbackController::class);
        });

        it('callback route is accessible', function () {
            $response = $this->get('/fatture-in-cloud/callback');

            // Should return 400 because no parameters are provided, but route exists and is accessible
            expect($response->status())->toBe(400);
        });

        it('named route generation works', function () {
            $url = route('fatture-in-cloud.callback');

            expect($url)->toContain('/fatture-in-cloud/callback');
            expect($url)->toStartWith('http');
        });
    });

    describe('Facade Registration', function () {
        it('facade is automatically registered', function () {
            expect(class_exists(\Codeman\FattureInCloud\Facades\FattureInCloud::class))->toBeTrue();
        });

        it('facade resolves to correct service', function () {
            $sdkFromContainer = app(FattureInCloudSdk::class);
            $sdkFromFacade = FattureInCloud::getFacadeRoot();

            expect($sdkFromFacade)->toBe($sdkFromContainer);
        });

        it('facade methods work correctly', function () {
            expect(function () {
                FattureInCloud::auth();
                FattureInCloud::getCompany();
                FattureInCloud::isTokenExpired();
            })->not->toThrow(\Exception::class);
        });
    });

    describe('Package Health Check', function () {
        it('all services can be resolved without configuration', function () {
            // Clear all configuration to test graceful handling
            config()->set('fatture-in-cloud.client_id', null);
            config()->set('fatture-in-cloud.client_secret', null);
            config()->set('fatture-in-cloud.redirect_url', null);
            config()->set('fatture-in-cloud.access_token', null);

            // These should not throw exceptions even with empty configuration
            expect(function () {
                app(StateManagerContract::class);
                app(TokenStorageContract::class);
                app(ApiServiceFactoryContract::class);
                app(FattureInCloudSdk::class);
                // OAuth2Manager might not be available without configuration
            })->not->toThrow(\Exception::class);
        });

        it('package works with minimal configuration', function () {
            config()->set('fatture-in-cloud.client_id', 'test-client-id');
            config()->set('fatture-in-cloud.client_secret', 'test-client-secret');

            expect(function () {
                app(OAuth2ManagerContract::class);
                app(StateManagerContract::class);
                app(TokenStorageContract::class);
                app(ApiServiceFactoryContract::class);
                app(FattureInCloudSdk::class);
            })->not->toThrow(\Exception::class);
        });

        it('all API services are available through factory', function () {
            $factory = app(ApiServiceFactoryContract::class);

            $expectedServices = [
                'clients' => ClientsApi::class,
                'companies' => CompaniesApi::class,
                'info' => InfoApi::class,
                'issuedDocuments' => IssuedDocumentsApi::class,
                'products' => ProductsApi::class,
                'receipts' => ReceiptsApi::class,
                'receivedDocuments' => ReceivedDocumentsApi::class,
                'suppliers' => SuppliersApi::class,
                'taxes' => TaxesApi::class,
                'user' => UserApi::class,
                'settings' => SettingsApi::class,
                'archive' => ArchiveApi::class,
                'cashbook' => CashbookApi::class,
                'priceLists' => PriceListsApi::class,
            ];

            foreach ($expectedServices as $serviceName => $expectedClass) {
                expect($factory->supports($serviceName))->toBeTrue();

                $service = $factory->make($serviceName);
                expect($service)->toBeInstanceOf($expectedClass);
            }
        });

        it('facade provides access to all API services', function () {
            expect(FattureInCloud::clients())->toBeInstanceOf(ClientsApi::class);
            expect(FattureInCloud::companies())->toBeInstanceOf(CompaniesApi::class);
            expect(FattureInCloud::info())->toBeInstanceOf(InfoApi::class);
            expect(FattureInCloud::issuedDocuments())->toBeInstanceOf(IssuedDocumentsApi::class);
            expect(FattureInCloud::products())->toBeInstanceOf(ProductsApi::class);
            expect(FattureInCloud::receipts())->toBeInstanceOf(ReceiptsApi::class);
            expect(FattureInCloud::receivedDocuments())->toBeInstanceOf(ReceivedDocumentsApi::class);
            expect(FattureInCloud::suppliers())->toBeInstanceOf(SuppliersApi::class);
            expect(FattureInCloud::taxes())->toBeInstanceOf(TaxesApi::class);
            expect(FattureInCloud::user())->toBeInstanceOf(UserApi::class);
            expect(FattureInCloud::settings())->toBeInstanceOf(SettingsApi::class);
            expect(FattureInCloud::archive())->toBeInstanceOf(ArchiveApi::class);
            expect(FattureInCloud::cashbook())->toBeInstanceOf(CashbookApi::class);
            expect(FattureInCloud::priceLists())->toBeInstanceOf(PriceListsApi::class);
        });
    });

    describe('Configuration Integration', function () {
        it('loads configuration from config file', function () {
            expect(config('fatture-in-cloud'))->toBeArray();
            expect(config('fatture-in-cloud.client_id'))->not->toBeNull();
            expect(config('fatture-in-cloud.client_secret'))->not->toBeNull();
        });

        it('uses environment variables correctly', function () {
            // These are set in TestCase::getEnvironmentSetUp
            expect(config('fatture-in-cloud.client_id'))->toBe('test-client-id');
            expect(config('fatture-in-cloud.client_secret'))->toBe('test-client-secret');
            expect(config('fatture-in-cloud.redirect_url'))->toBe('http://localhost/fatture-in-cloud/callback');
        });

        it('handles different authentication modes', function () {
            // OAuth2 mode (current test setup)
            config()->set('fatture-in-cloud.access_token', null);
            expect(function () {
                app(OAuth2ManagerContract::class);
            })->not->toThrow(\Exception::class);

            // Manual authentication mode
            config()->set('fatture-in-cloud.access_token', 'manual-token');
            $apiFactory = app(ApiServiceFactoryContract::class);
            expect($apiFactory)->toBeInstanceOf(FattureInCloudApiServiceFactory::class);
        });
    });

    describe('Error Handling and Edge Cases', function () {
        it('handles missing OAuth2 configuration gracefully', function () {
            config()->set('fatture-in-cloud.client_id', null);
            config()->set('fatture-in-cloud.client_secret', null);

            // Should still be able to resolve most services
            expect(function () {
                app(StateManagerContract::class);
                app(TokenStorageContract::class);
                app(ApiServiceFactoryContract::class);
                app(FattureInCloudSdk::class);
            })->not->toThrow(\Exception::class);
        });

        it('works with different Laravel cache drivers', function () {
            config()->set('cache.default', 'array');
            $tokenStorage = app(TokenStorageContract::class);
            expect($tokenStorage)->toBeInstanceOf(CacheTokenStorage::class);

            // Test basic token storage functionality
            expect(function () use ($tokenStorage) {
                // Store a token first, then clear it
                $tokenResponse = new \FattureInCloud\OAuth2\OAuth2TokenResponse('Bearer', 'test-token', 'refresh-token', 3600);
                $tokenStorage->store('test-context', $tokenResponse);
                $tokenStorage->clear('test-context');
            })->not->toThrow(\Exception::class);
        });

        it('works with different Laravel session drivers', function () {
            config()->set('session.driver', 'array');
            $stateManager = app(StateManagerContract::class);
            expect($stateManager)->toBeInstanceOf(SessionStateManager::class);

            // Test basic state management functionality
            expect(function () use ($stateManager) {
                $stateManager->store('test-state');
                $stateManager->validate('test-state');
                $stateManager->clear();
            })->not->toThrow(\Exception::class);
        });
    });
});
