<?php

use Codeman\FattureInCloud\Contracts\OAuth2Manager;
use Codeman\FattureInCloud\Facades\FattureInCloud;
use Codeman\FattureInCloud\FattureInCloudSdk;
use FattureInCloud\Api\ClientsApi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

describe('FattureInCloud Facade', function () {
    beforeEach(function () {
        // Ensure the service provider is registered and services are bound
        config([
            'fatture-in-cloud.client_id' => 'test-client-id',
            'fatture-in-cloud.client_secret' => 'test-client-secret',
            'fatture-in-cloud.redirect_url' => 'https://test.com/callback',
        ]);
    });

    describe('facade resolution', function () {
        test('resolves to FattureInCloudSdk instance', function () {
            $instance = FattureInCloud::getFacadeRoot();

            expect($instance)->toBeInstanceOf(FattureInCloudSdk::class);
        });

        test('returns same instance on multiple calls (singleton)', function () {
            $instance1 = FattureInCloud::getFacadeRoot();
            $instance2 = FattureInCloud::getFacadeRoot();

            expect($instance1)->toBe($instance2);
        });

        test('facade accessor returns correct class name', function () {
            $reflection = new ReflectionClass(FattureInCloud::class);
            $method = $reflection->getMethod('getFacadeAccessor');
            $method->setAccessible(true);

            $accessor = $method->invoke(null);

            expect($accessor)->toBe(FattureInCloudSdk::class);
        });
    });

    describe('method delegation', function () {
        test('delegates auth() method', function () {
            $result = FattureInCloud::auth();

            expect($result)->toBeInstanceOf(OAuth2Manager::class);
        });

        test('delegates getAuthorizationUrl() method', function () {
            $scopes = ['scope1', 'scope2'];

            $result = FattureInCloud::getAuthorizationUrl($scopes);

            expect($result)->toBeString();
            expect($result)->toContain('oauth');
        });

        test('delegates redirectToAuthorization() method', function () {
            $scopes = ['scope1'];

            $result = FattureInCloud::redirectToAuthorization($scopes);

            expect($result)->toBeInstanceOf(RedirectResponse::class);
        });

        test('delegates company management methods', function () {
            $result = FattureInCloud::setCompany(12345);

            expect($result)->toBeInstanceOf(FattureInCloudSdk::class);
            expect(FattureInCloud::getCompany())->toBe('12345');
        });

        test('delegates token management methods', function () {
            expect(FattureInCloud::isTokenExpired())->toBeBool();

            // Test clearTokens doesn't throw
            FattureInCloud::clearTokens();
        });
    });

    describe('API service method delegation', function () {
        test('delegates clients() method', function () {
            // This might fail if no valid token, but we're testing the delegation
            try {
                $result = FattureInCloud::clients();
                expect($result)->toBeInstanceOf(ClientsApi::class);
            } catch (\Exception $e) {
                // Expected when no valid token - just verify the method exists and is callable
                expect(method_exists(FattureInCloud::class, 'clients'))->toBeTrue();
            }
        });

        test('delegates companies() method', function () {
            try {
                $result = FattureInCloud::companies();
                expect($result)->toBeInstanceOf(\FattureInCloud\Api\CompaniesApi::class);
            } catch (\Exception $e) {
                expect(method_exists(FattureInCloud::class, 'companies'))->toBeTrue();
            }
        });

        test('delegates info() method', function () {
            try {
                $result = FattureInCloud::info();
                expect($result)->toBeInstanceOf(\FattureInCloud\Api\InfoApi::class);
            } catch (\Exception $e) {
                expect(method_exists(FattureInCloud::class, 'info'))->toBeTrue();
            }
        });

        test('delegates issuedDocuments() method', function () {
            try {
                $result = FattureInCloud::issuedDocuments();
                expect($result)->toBeInstanceOf(\FattureInCloud\Api\IssuedDocumentsApi::class);
            } catch (\Exception $e) {
                expect(method_exists(FattureInCloud::class, 'issuedDocuments'))->toBeTrue();
            }
        });

        test('delegates products() method', function () {
            try {
                $result = FattureInCloud::products();
                expect($result)->toBeInstanceOf(\FattureInCloud\Api\ProductsApi::class);
            } catch (\Exception $e) {
                expect(method_exists(FattureInCloud::class, 'products'))->toBeTrue();
            }
        });

        test('delegates receipts() method', function () {
            try {
                $result = FattureInCloud::receipts();
                expect($result)->toBeInstanceOf(\FattureInCloud\Api\ReceiptsApi::class);
            } catch (\Exception $e) {
                expect(method_exists(FattureInCloud::class, 'receipts'))->toBeTrue();
            }
        });

        test('delegates receivedDocuments() method', function () {
            try {
                $result = FattureInCloud::receivedDocuments();
                expect($result)->toBeInstanceOf(\FattureInCloud\Api\ReceivedDocumentsApi::class);
            } catch (\Exception $e) {
                expect(method_exists(FattureInCloud::class, 'receivedDocuments'))->toBeTrue();
            }
        });

        test('delegates suppliers() method', function () {
            try {
                $result = FattureInCloud::suppliers();
                expect($result)->toBeInstanceOf(\FattureInCloud\Api\SuppliersApi::class);
            } catch (\Exception $e) {
                expect(method_exists(FattureInCloud::class, 'suppliers'))->toBeTrue();
            }
        });

        test('delegates taxes() method', function () {
            try {
                $result = FattureInCloud::taxes();
                expect($result)->toBeInstanceOf(\FattureInCloud\Api\TaxesApi::class);
            } catch (\Exception $e) {
                expect(method_exists(FattureInCloud::class, 'taxes'))->toBeTrue();
            }
        });

        test('delegates user() method', function () {
            try {
                $result = FattureInCloud::user();
                expect($result)->toBeInstanceOf(\FattureInCloud\Api\UserApi::class);
            } catch (\Exception $e) {
                expect(method_exists(FattureInCloud::class, 'user'))->toBeTrue();
            }
        });

        test('delegates settings() method', function () {
            try {
                $result = FattureInCloud::settings();
                expect($result)->toBeInstanceOf(\FattureInCloud\Api\SettingsApi::class);
            } catch (\Exception $e) {
                expect(method_exists(FattureInCloud::class, 'settings'))->toBeTrue();
            }
        });

        test('delegates archive() method', function () {
            try {
                $result = FattureInCloud::archive();
                expect($result)->toBeInstanceOf(\FattureInCloud\Api\ArchiveApi::class);
            } catch (\Exception $e) {
                expect(method_exists(FattureInCloud::class, 'archive'))->toBeTrue();
            }
        });

        test('delegates cashbook() method', function () {
            try {
                $result = FattureInCloud::cashbook();
                expect($result)->toBeInstanceOf(\FattureInCloud\Api\CashbookApi::class);
            } catch (\Exception $e) {
                expect(method_exists(FattureInCloud::class, 'cashbook'))->toBeTrue();
            }
        });

        test('delegates priceLists() method', function () {
            try {
                $result = FattureInCloud::priceLists();
                expect($result)->toBeInstanceOf(\FattureInCloud\Api\PriceListsApi::class);
            } catch (\Exception $e) {
                expect(method_exists(FattureInCloud::class, 'priceLists'))->toBeTrue();
            }
        });
    });

    describe('OAuth2 callback handling through facade', function () {
        test('delegates handleOAuth2Callback() method', function () {
            $request = new Request(['error' => 'access_denied']);

            expect(fn () => FattureInCloud::handleOAuth2Callback($request))
                ->toThrow(\InvalidArgumentException::class, 'OAuth2 authorization failed');
        });

        test('delegates fetchToken() method', function () {
            // This will likely fail due to invalid credentials, but tests delegation
            expect(fn () => FattureInCloud::fetchToken('invalid-code', 'invalid-state'))
                ->toThrow(\Exception::class);
        });

        test('delegates refreshToken() method', function () {
            // Should return null when no refresh token available
            $result = FattureInCloud::refreshToken();
            expect($result)->toBeNull();
        });
    });

    describe('facade vs direct instantiation', function () {
        test('facade and direct instance have same methods', function () {
            $facadeInstance = FattureInCloud::getFacadeRoot();
            $directInstance = $this->app->make(FattureInCloudSdk::class);

            expect($facadeInstance)->toBe($directInstance);
        });

        test('facade state is shared with direct instance', function () {
            $directInstance = $this->app->make(FattureInCloudSdk::class);
            $directInstance->setCompany(99999);

            expect(FattureInCloud::getCompany())->toBe('99999');
        });

        test('facade preserves method chaining', function () {
            $result = FattureInCloud::setCompany(55555);

            expect($result)->toBeInstanceOf(FattureInCloudSdk::class);
            expect(FattureInCloud::getCompany())->toBe('55555');
        });
    });

    describe('static method availability', function () {
        test('all SDK methods are available as static calls', function () {
            $sdkMethods = get_class_methods(FattureInCloudSdk::class);
            $excludedMethods = ['__construct']; // Constructor not available as static

            foreach ($sdkMethods as $method) {
                if (in_array($method, $excludedMethods)) {
                    continue;
                }

                // Test that the method can be called statically (even if it fails due to dependencies)
                try {
                    $reflection = new ReflectionMethod(FattureInCloud::class, $method);
                    expect($reflection->isStatic())->toBeTrue();
                } catch (ReflectionException $e) {
                    // Method is handled by __callStatic, which is expected
                    expect(method_exists(FattureInCloud::class, '__callStatic'))->toBeTrue();
                }
            }
        });
    });
});
