<?php

use Codeman\FattureInCloud\Contracts\ApiServiceFactory;
use Codeman\FattureInCloud\Contracts\OAuth2Manager;
use Codeman\FattureInCloud\Contracts\TokenStorage;
use Codeman\FattureInCloud\FattureInCloudSdk;
use FattureInCloud\Api\ArchiveApi;
use FattureInCloud\Api\CashbookApi;
use FattureInCloud\Api\ClientsApi;
use FattureInCloud\Api\CompaniesApi;
use FattureInCloud\Api\InfoApi;
use FattureInCloud\Api\IssuedDocumentsApi;
use FattureInCloud\Api\PriceListsApi;
use FattureInCloud\Api\ProductsApi;
use FattureInCloud\Api\ReceiptsApi;
use FattureInCloud\Api\ReceivedDocumentsApi;
use FattureInCloud\Api\SettingsApi;
use FattureInCloud\Api\SuppliersApi;
use FattureInCloud\Api\TaxesApi;
use FattureInCloud\Api\UserApi;
use FattureInCloud\OAuth2\OAuth2TokenResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

describe('FattureInCloudSdk', function () {
    beforeEach(function () {
        $this->oauthManager = Mockery::mock(OAuth2Manager::class);
        $this->tokenStorage = Mockery::mock(TokenStorage::class);
        $this->apiFactory = Mockery::mock(ApiServiceFactory::class);
        
        $this->sdk = new FattureInCloudSdk(
            $this->oauthManager,
            $this->tokenStorage,
            $this->apiFactory
        );
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('constructor and basic properties', function () {
        test('can be instantiated with dependencies', function () {
            expect($this->sdk)->toBeInstanceOf(FattureInCloudSdk::class);
        });

        test('sets default context key', function () {
            $sdk = new FattureInCloudSdk(
                $this->oauthManager,
                $this->tokenStorage,
                $this->apiFactory
            );
            
            expect($sdk)->toBeInstanceOf(FattureInCloudSdk::class);
        });

        test('accepts custom context key', function () {
            $sdk = new FattureInCloudSdk(
                $this->oauthManager,
                $this->tokenStorage,
                $this->apiFactory,
                'custom-context'
            );
            
            expect($sdk)->toBeInstanceOf(FattureInCloudSdk::class);
        });
    });

    describe('auth manager access', function () {
        test('returns OAuth2Manager instance', function () {
            $result = $this->sdk->auth();
            
            expect($result)->toBe($this->oauthManager);
        });
    });

    describe('authorization URL generation', function () {
        test('delegates to OAuth2Manager', function () {
            $scopes = ['scope1', 'scope2'];
            $state = 'test-state';
            $expectedUrl = 'https://api-v2.fattureincloud.it/oauth/authorize?...';

            $this->oauthManager->shouldReceive('getAuthorizationUrl')
                ->once()
                ->with($scopes, $state)
                ->andReturn($expectedUrl);

            $result = $this->sdk->getAuthorizationUrl($scopes, $state);

            expect($result)->toBe($expectedUrl);
        });

        test('handles null state parameter', function () {
            $scopes = ['scope1'];
            $expectedUrl = 'https://api-v2.fattureincloud.it/oauth/authorize?...';

            $this->oauthManager->shouldReceive('getAuthorizationUrl')
                ->once()
                ->with($scopes, null)
                ->andReturn($expectedUrl);

            $result = $this->sdk->getAuthorizationUrl($scopes);

            expect($result)->toBe($expectedUrl);
        });
    });

    describe('redirect to authorization', function () {
        test('returns redirect response when OAuth2Manager is initialized', function () {
            $scopes = ['scope1', 'scope2'];
            $authUrl = 'https://api-v2.fattureincloud.it/oauth/authorize?...';

            $this->oauthManager->shouldReceive('isInitialized')
                ->once()
                ->andReturn(true);

            $this->oauthManager->shouldReceive('getAuthorizationUrl')
                ->once()
                ->with($scopes, null)
                ->andReturn($authUrl);

            $result = $this->sdk->redirectToAuthorization($scopes);

            expect($result)->toBeInstanceOf(RedirectResponse::class);
            expect($result->getTargetUrl())->toBe($authUrl);
        });

        test('throws LogicException when OAuth2Manager is not initialized', function () {
            $scopes = ['scope1'];

            $this->oauthManager->shouldReceive('isInitialized')
                ->once()
                ->andReturn(false);

            expect(fn () => $this->sdk->redirectToAuthorization($scopes))
                ->toThrow(LogicException::class, 'OAuth2 manager is not initialized');
        });
    });

    describe('token management', function () {
        test('fetchToken stores token and returns response', function () {
            $code = 'auth-code';
            $state = 'csrf-state';
            $tokenResponse = Mockery::mock(OAuth2TokenResponse::class);

            $this->oauthManager->shouldReceive('fetchToken')
                ->once()
                ->with($code, $state)
                ->andReturn($tokenResponse);

            $this->tokenStorage->shouldReceive('store')
                ->once()
                ->with('default', $tokenResponse);

            $result = $this->sdk->fetchToken($code, $state);

            expect($result)->toBe($tokenResponse);
        });

        test('refreshToken with valid refresh token', function () {
            $refreshToken = 'refresh-token';
            $tokenResponse = Mockery::mock(OAuth2TokenResponse::class);

            $this->tokenStorage->shouldReceive('getRefreshToken')
                ->once()
                ->with('default')
                ->andReturn($refreshToken);

            $this->oauthManager->shouldReceive('refreshToken')
                ->once()
                ->with($refreshToken)
                ->andReturn($tokenResponse);

            $this->tokenStorage->shouldReceive('store')
                ->once()
                ->with('default', $tokenResponse);

            $result = $this->sdk->refreshToken();

            expect($result)->toBe($tokenResponse);
        });

        test('refreshToken returns null when no refresh token available', function () {
            $this->tokenStorage->shouldReceive('getRefreshToken')
                ->once()
                ->with('default')
                ->andReturn(null);

            $result = $this->sdk->refreshToken();

            expect($result)->toBeNull();
        });

        test('refreshToken clears tokens on exception', function () {
            $refreshToken = 'refresh-token';
            $exception = new Exception('Refresh failed');

            $this->tokenStorage->shouldReceive('getRefreshToken')
                ->once()
                ->with('default')
                ->andReturn($refreshToken);

            $this->oauthManager->shouldReceive('refreshToken')
                ->once()
                ->with($refreshToken)
                ->andThrow($exception);

            $this->tokenStorage->shouldReceive('clear')
                ->once()
                ->with('default');

            expect(fn () => $this->sdk->refreshToken())
                ->toThrow(Exception::class, 'Refresh failed');
        });

        test('isTokenExpired delegates to token storage', function () {
            $this->tokenStorage->shouldReceive('isExpired')
                ->once()
                ->with('default')
                ->andReturn(true);

            $result = $this->sdk->isTokenExpired();

            expect($result)->toBeTrue();
        });

        test('clearTokens delegates to token storage', function () {
            $this->tokenStorage->shouldReceive('clear')
                ->once()
                ->with('default');

            $this->sdk->clearTokens();
        });
    });

    describe('OAuth2 callback handling', function () {
        test('handles successful callback', function () {
            $request = new Request(['code' => 'auth-code', 'state' => 'csrf-state']);
            $tokenResponse = Mockery::mock(OAuth2TokenResponse::class);

            $this->oauthManager->shouldReceive('fetchToken')
                ->once()
                ->with('auth-code', 'csrf-state')
                ->andReturn($tokenResponse);

            $this->tokenStorage->shouldReceive('store')
                ->once()
                ->with('default', $tokenResponse);

            $result = $this->sdk->handleOAuth2Callback($request);

            expect($result)->toBe($tokenResponse);
        });

        test('handles OAuth2 error response', function () {
            $request = new Request([
                'error' => 'access_denied',
                'error_description' => 'User denied access'
            ]);

            expect(fn () => $this->sdk->handleOAuth2Callback($request))
                ->toThrow(InvalidArgumentException::class, 'OAuth2 authorization failed: access_denied - User denied access');
        });

        test('handles OAuth2 error without description', function () {
            $request = new Request(['error' => 'invalid_request']);

            expect(fn () => $this->sdk->handleOAuth2Callback($request))
                ->toThrow(InvalidArgumentException::class, 'OAuth2 authorization failed: invalid_request');
        });

        test('handles missing code parameter', function () {
            $request = new Request(['state' => 'csrf-state']);

            expect(fn () => $this->sdk->handleOAuth2Callback($request))
                ->toThrow(InvalidArgumentException::class, 'Missing required OAuth2 callback parameters');
        });

        test('handles missing state parameter', function () {
            $request = new Request(['code' => 'auth-code']);

            expect(fn () => $this->sdk->handleOAuth2Callback($request))
                ->toThrow(InvalidArgumentException::class, 'Missing required OAuth2 callback parameters');
        });
    });

    describe('company management', function () {
        test('setCompany stores company ID as string', function () {
            $result = $this->sdk->setCompany(12345);

            expect($result)->toBe($this->sdk);
            expect($this->sdk->getCompany())->toBe('12345');
        });

        test('getCompany returns null initially', function () {
            expect($this->sdk->getCompany())->toBeNull();
        });

        test('getCompany returns set company ID', function () {
            $this->sdk->setCompany(98765);
            
            expect($this->sdk->getCompany())->toBe('98765');
        });
    });

    describe('API service methods', function () {
        beforeEach(function () {
            // Mock token validation to pass
            $this->tokenStorage->shouldReceive('isExpired')->andReturn(false);
        });

        test('clients() returns ClientsApi', function () {
            $clientsApi = Mockery::mock(ClientsApi::class);

            $this->apiFactory->shouldReceive('make')
                ->once()
                ->with('clients')
                ->andReturn($clientsApi);

            $result = $this->sdk->clients();

            expect($result)->toBe($clientsApi);
        });

        test('companies() returns CompaniesApi', function () {
            $companiesApi = Mockery::mock(CompaniesApi::class);

            $this->apiFactory->shouldReceive('make')
                ->once()
                ->with('companies')
                ->andReturn($companiesApi);

            $result = $this->sdk->companies();

            expect($result)->toBe($companiesApi);
        });

        test('info() returns InfoApi', function () {
            $infoApi = Mockery::mock(InfoApi::class);

            $this->apiFactory->shouldReceive('make')
                ->once()
                ->with('info')
                ->andReturn($infoApi);

            $result = $this->sdk->info();

            expect($result)->toBe($infoApi);
        });

        test('issuedDocuments() returns IssuedDocumentsApi', function () {
            $issuedDocumentsApi = Mockery::mock(IssuedDocumentsApi::class);

            $this->apiFactory->shouldReceive('make')
                ->once()
                ->with('issuedDocuments')
                ->andReturn($issuedDocumentsApi);

            $result = $this->sdk->issuedDocuments();

            expect($result)->toBe($issuedDocumentsApi);
        });

        test('products() returns ProductsApi', function () {
            $productsApi = Mockery::mock(ProductsApi::class);

            $this->apiFactory->shouldReceive('make')
                ->once()
                ->with('products')
                ->andReturn($productsApi);

            $result = $this->sdk->products();

            expect($result)->toBe($productsApi);
        });

        test('receipts() returns ReceiptsApi', function () {
            $receiptsApi = Mockery::mock(ReceiptsApi::class);

            $this->apiFactory->shouldReceive('make')
                ->once()
                ->with('receipts')
                ->andReturn($receiptsApi);

            $result = $this->sdk->receipts();

            expect($result)->toBe($receiptsApi);
        });

        test('receivedDocuments() returns ReceivedDocumentsApi', function () {
            $receivedDocumentsApi = Mockery::mock(ReceivedDocumentsApi::class);

            $this->apiFactory->shouldReceive('make')
                ->once()
                ->with('receivedDocuments')
                ->andReturn($receivedDocumentsApi);

            $result = $this->sdk->receivedDocuments();

            expect($result)->toBe($receivedDocumentsApi);
        });

        test('suppliers() returns SuppliersApi', function () {
            $suppliersApi = Mockery::mock(SuppliersApi::class);

            $this->apiFactory->shouldReceive('make')
                ->once()
                ->with('suppliers')
                ->andReturn($suppliersApi);

            $result = $this->sdk->suppliers();

            expect($result)->toBe($suppliersApi);
        });

        test('taxes() returns TaxesApi', function () {
            $taxesApi = Mockery::mock(TaxesApi::class);

            $this->apiFactory->shouldReceive('make')
                ->once()
                ->with('taxes')
                ->andReturn($taxesApi);

            $result = $this->sdk->taxes();

            expect($result)->toBe($taxesApi);
        });

        test('user() returns UserApi', function () {
            $userApi = Mockery::mock(UserApi::class);

            $this->apiFactory->shouldReceive('make')
                ->once()
                ->with('user')
                ->andReturn($userApi);

            $result = $this->sdk->user();

            expect($result)->toBe($userApi);
        });

        test('settings() returns SettingsApi', function () {
            $settingsApi = Mockery::mock(SettingsApi::class);

            $this->apiFactory->shouldReceive('make')
                ->once()
                ->with('settings')
                ->andReturn($settingsApi);

            $result = $this->sdk->settings();

            expect($result)->toBe($settingsApi);
        });

        test('archive() returns ArchiveApi', function () {
            $archiveApi = Mockery::mock(ArchiveApi::class);

            $this->apiFactory->shouldReceive('make')
                ->once()
                ->with('archive')
                ->andReturn($archiveApi);

            $result = $this->sdk->archive();

            expect($result)->toBe($archiveApi);
        });

        test('cashbook() returns CashbookApi', function () {
            $cashbookApi = Mockery::mock(CashbookApi::class);

            $this->apiFactory->shouldReceive('make')
                ->once()
                ->with('cashbook')
                ->andReturn($cashbookApi);

            $result = $this->sdk->cashbook();

            expect($result)->toBe($cashbookApi);
        });

        test('priceLists() returns PriceListsApi', function () {
            $priceListsApi = Mockery::mock(PriceListsApi::class);

            $this->apiFactory->shouldReceive('make')
                ->once()
                ->with('priceLists')
                ->andReturn($priceListsApi);

            $result = $this->sdk->priceLists();

            expect($result)->toBe($priceListsApi);
        });
    });

    describe('token validation and refresh', function () {
        test('API methods refresh token when expired and OAuth2Manager is initialized', function () {
            $clientsApi = Mockery::mock(ClientsApi::class);
            $refreshToken = 'refresh-token';
            $tokenResponse = Mockery::mock(OAuth2TokenResponse::class);

            // Token is expired
            $this->tokenStorage->shouldReceive('isExpired')
                ->once()
                ->with('default')
                ->andReturn(true);

            // OAuth2Manager is initialized
            $this->oauthManager->shouldReceive('isInitialized')
                ->once()
                ->andReturn(true);

            // Refresh token flow
            $this->tokenStorage->shouldReceive('getRefreshToken')
                ->once()
                ->with('default')
                ->andReturn($refreshToken);

            $this->oauthManager->shouldReceive('refreshToken')
                ->once()
                ->with($refreshToken)
                ->andReturn($tokenResponse);

            $this->tokenStorage->shouldReceive('store')
                ->once()
                ->with('default', $tokenResponse);

            // API service creation
            $this->apiFactory->shouldReceive('make')
                ->once()
                ->with('clients')
                ->andReturn($clientsApi);

            $result = $this->sdk->clients();

            expect($result)->toBe($clientsApi);
        });

        test('API methods skip refresh when OAuth2Manager is not initialized', function () {
            $clientsApi = Mockery::mock(ClientsApi::class);

            // Token is expired
            $this->tokenStorage->shouldReceive('isExpired')
                ->once()
                ->with('default')
                ->andReturn(true);

            // OAuth2Manager is not initialized
            $this->oauthManager->shouldReceive('isInitialized')
                ->once()
                ->andReturn(false);

            // Should not attempt refresh, just create service
            $this->apiFactory->shouldReceive('make')
                ->once()
                ->with('clients')
                ->andReturn($clientsApi);

            $result = $this->sdk->clients();

            expect($result)->toBe($clientsApi);
        });

        test('API methods work normally when token is not expired', function () {
            $clientsApi = Mockery::mock(ClientsApi::class);

            // Token is not expired
            $this->tokenStorage->shouldReceive('isExpired')
                ->once()
                ->with('default')
                ->andReturn(false);

            // Should not attempt refresh, just create service
            $this->apiFactory->shouldReceive('make')
                ->once()
                ->with('clients')
                ->andReturn($clientsApi);

            $result = $this->sdk->clients();

            expect($result)->toBe($clientsApi);
        });
    });
});