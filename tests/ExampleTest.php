<?php

use Codeman\FattureInCloud\Contracts\ApiServiceFactory as ApiServiceFactoryContract;
use Codeman\FattureInCloud\Contracts\OAuth2Manager as OAuth2ManagerContract;
use Codeman\FattureInCloud\Contracts\TokenStorage as TokenStorageContract;
use Codeman\FattureInCloud\Facades\FattureInCloud;
use Codeman\FattureInCloud\FattureInCloudSdk;

describe('Package Basic Functionality', function () {
    describe('Service Container Integration', function () {
        it('registers all contracts in the service container', function () {
            expect(app()->bound(OAuth2ManagerContract::class))->toBeTrue();
            expect(app()->bound(TokenStorageContract::class))->toBeTrue();
            expect(app()->bound(ApiServiceFactoryContract::class))->toBeTrue();
            expect(app()->bound(FattureInCloudSdk::class))->toBeTrue();
        });

        it('resolves services as singletons', function () {
            $sdk1 = app(FattureInCloudSdk::class);
            $sdk2 = app(FattureInCloudSdk::class);

            expect($sdk1)->toBe($sdk2);
        });

        it('creates SDK with properly injected dependencies', function () {
            $sdk = app(FattureInCloudSdk::class);

            expect($sdk)->toBeInstanceOf(FattureInCloudSdk::class);
            expect($sdk->auth())->toBeInstanceOf(OAuth2ManagerContract::class);
        });
    });

    describe('Facade Integration', function () {
        it('facade resolves to the correct service', function () {
            $sdkFromContainer = app(FattureInCloudSdk::class);
            $sdkFromFacade = FattureInCloud::getFacadeRoot();

            expect($sdkFromFacade)->toBe($sdkFromContainer);
        });

        it('facade maintains service consistency', function () {
            $auth1 = FattureInCloud::auth();
            $auth2 = FattureInCloud::auth();

            expect($auth1)->toBe($auth2);
        });
    });

    describe('Configuration Loading', function () {
        it('loads package configuration correctly', function () {
            expect(config('fatture-in-cloud.client_id'))->toBe('test-client-id');
            expect(config('fatture-in-cloud.client_secret'))->toBe('test-client-secret');
            expect(config('fatture-in-cloud.redirect_url'))->toBe('http://localhost/fatture-in-cloud/callback');
        });

        it('handles missing configuration gracefully', function () {
            config()->set('fatture-in-cloud.client_id', null);
            config()->set('fatture-in-cloud.client_secret', null);

            expect(function () {
                app(OAuth2ManagerContract::class);
            })->not->toThrow(\Exception::class);
        });
    });

    describe('Route Registration', function () {
        it('registers OAuth2 callback route', function () {
            $response = $this->get('/fatture-in-cloud/callback');

            // Should return 400 because no parameters are provided, but route exists
            $response->assertStatus(400);
        });

        it('callback route has correct name', function () {
            $url = route('fatture-in-cloud.callback');

            expect($url)->toContain('fatture-in-cloud/callback');
        });
    });

    describe('Package Integration Health Check', function () {
        it('all core services can be resolved without errors', function () {
            expect(function () {
                app(TokenStorageContract::class);
                app(OAuth2ManagerContract::class);
                app(ApiServiceFactoryContract::class);
                app(FattureInCloudSdk::class);
            })->not->toThrow(\Exception::class);
        });

        it('API factory supports expected services', function () {
            $factory = app(ApiServiceFactoryContract::class);

            $expectedServices = [
                'clients', 'companies', 'info', 'issuedDocuments',
                'products', 'receipts', 'receivedDocuments', 'suppliers',
                'taxes', 'user', 'settings', 'archive', 'cashbook', 'priceLists',
            ];

            foreach ($expectedServices as $service) {
                expect($factory->supports($service))->toBeTrue();
            }
        });

        it('facade can access all main SDK methods', function () {
            expect(function () {
                FattureInCloud::auth();
                FattureInCloud::getCompany();
                FattureInCloud::isTokenExpired();
            })->not->toThrow(\Exception::class);
        });
    });

    describe('Environment Configuration', function () {
        it('uses test environment configuration correctly', function () {
            expect(app()->environment())->toBe('testing');
            expect(config('cache.default'))->toBe('array');
            expect(config('session.driver'))->toBe('array');
        });

        it('encryption key is properly configured for testing', function () {
            expect(config('app.key'))->toStartWith('base64:');
        });
    });
});
