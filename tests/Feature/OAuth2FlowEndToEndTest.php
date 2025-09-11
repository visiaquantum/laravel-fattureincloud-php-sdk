<?php

use Codeman\FattureInCloud\Contracts\OAuth2Manager as OAuth2ManagerContract;
use Codeman\FattureInCloud\Contracts\StateManager as StateManagerContract;
use Codeman\FattureInCloud\Contracts\TokenStorage as TokenStorageContract;
use Codeman\FattureInCloud\Facades\FattureInCloud;
use Codeman\FattureInCloud\FattureInCloudSdk;
use FattureInCloud\OAuth2\OAuth2Error;
use FattureInCloud\OAuth2\OAuth2TokenResponse;
use FattureInCloud\OAuth2\Scope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

describe('OAuth2 Flow End-to-End', function () {
    beforeEach(function () {
        // Clear any existing tokens
        app(TokenStorageContract::class)->clear('default');
        
        // Set up OAuth2 configuration
        config()->set('fatture-in-cloud.client_id', 'test-client-id');
        config()->set('fatture-in-cloud.client_secret', 'test-client-secret');
        config()->set('fatture-in-cloud.redirect_url', 'http://localhost/fatture-in-cloud/callback');
        config()->set('fatture-in-cloud.access_token', null); // Use OAuth2 flow
    });
    
    afterEach(function () {
        Mockery::close();
    });
    
    describe('Complete OAuth2 Authorization Code Flow', function () {
        it('completes full authorization flow successfully', function () {
            $sdk = app(FattureInCloudSdk::class);
            $oauth2Manager = $sdk->auth();
            $stateManager = app(StateManagerContract::class);
            $tokenStorage = app(TokenStorageContract::class);
            
            // Step 1: Generate authorization URL
            $scopes = [Scope::ENTITY_CLIENTS_ALL, Scope::ISSUED_DOCUMENTS_INVOICES_ALL];
            $authUrl = $oauth2Manager->getAuthorizationUrl($scopes);
            
            expect($authUrl)->toBeString();
            expect($authUrl)->toContain('https://api-v2.fattureincloud.it/oauth/authorize');
            expect($authUrl)->toContain('client_id=test-client-id');
            expect($authUrl)->toContain('response_type=code');
            expect($authUrl)->toContain('redirect_uri=http%3A%2F%2Flocalhost%2Ffatture-in-cloud%2Fcallback');
            expect($authUrl)->toContain('scope=entity.clients%3Aa+issued_documents.invoices%3Aa');
            expect($authUrl)->toContain('state=');
            
            // Extract state from URL for later validation
            parse_str(parse_url($authUrl, PHP_URL_QUERY), $params);
            $generatedState = $params['state'];
            
            // Step 2: Simulate user authorization and callback
            // Mock the OAuth2Manager to return a successful token response
            $mockTokenResponse = new OAuth2TokenResponse('Bearer', 'test-access-token-123', 'test-refresh-token-123', 3600);
            
            $mockOAuth2Manager = Mockery::mock(OAuth2ManagerContract::class);
            $mockOAuth2Manager->shouldReceive('getAuthorizationUrl')
                ->andReturn($authUrl);
            $mockOAuth2Manager->shouldReceive('fetchToken')
                ->with('test-authorization-code')
                ->andReturn($mockTokenResponse);
            
            app()->instance(OAuth2ManagerContract::class, $mockOAuth2Manager);
            
            // Step 3: Handle callback with authorization code
            $callbackResponse = $sdk->handleCallback(
                Request::create('http://localhost/fatture-in-cloud/callback', 'GET', [
                    'code' => 'test-authorization-code',
                    'state' => $generatedState
                ])
            );
            
            expect($callbackResponse)->toBeInstanceOf(OAuth2TokenResponse::class);
            expect($callbackResponse->getAccessToken())->toBe('test-access-token-123');
            expect($callbackResponse->getRefreshToken())->toBe('test-refresh-token-123');
            
            // Step 4: Verify token storage
            $storedToken = $tokenStorage->getAccessToken('default');
            expect($storedToken)->toBe('test-access-token-123');
            
            $storedRefreshToken = $tokenStorage->getRefreshToken('default');
            expect($storedRefreshToken)->toBe('test-refresh-token-123');
        });
        
        it('handles authorization errors gracefully', function () {
            $sdk = app(FattureInCloudSdk::class);
            
            // Simulate OAuth2 error response (user denies access)
            expect(function () use ($sdk) {
                $sdk->handleCallback(
                    Request::create('http://localhost/fatture-in-cloud/callback', 'GET', [
                        'error' => 'access_denied',
                        'error_description' => 'The resource owner or authorization server denied the request'
                    ])
                );
            })->toThrow(InvalidArgumentException::class);
        });
        
        it('validates state parameter to prevent CSRF attacks', function () {
            $sdk = app(FattureInCloudSdk::class);
            $stateManager = app(StateManagerContract::class);
            
            // Generate a valid state
            $validState = $stateManager->generateState();
            
            // Try to use an invalid state
            expect(function () use ($sdk) {
                $sdk->handleCallback(
                    Request::create('http://localhost/fatture-in-cloud/callback', 'GET', [
                        'code' => 'test-authorization-code',
                        'state' => 'invalid-state'
                    ])
                );
            })->toThrow(InvalidArgumentException::class);
        });
    });
    
    describe('OAuth2 Flow via Facade', function () {
        it('supports complete flow via facade', function () {
            // Step 1: Generate authorization URL via facade
            $authUrl = FattureInCloud::getAuthorizationUrl([Scope::ENTITY_CLIENTS_READ]);
            
            expect($authUrl)->toBeString();
            expect($authUrl)->toContain('client_id=test-client-id');
            
            // Step 2: Test redirect helper method
            $redirectResponse = FattureInCloud::redirectToAuthorization([Scope::ENTITY_CLIENTS_READ]);
            
            expect($redirectResponse)->toBeInstanceOf(RedirectResponse::class);
            expect($redirectResponse->getTargetUrl())->toContain('oauth/authorize');
        });
        
        it('handles callback via facade helper method', function () {
            $stateManager = app(StateManagerContract::class);
            $validState = $stateManager->generateState();
            
            // Mock successful token exchange
            $mockTokenResponse = new OAuth2TokenResponse('Bearer', 'facade-token-123', 'facade-refresh-123', 3600);
            
            $mockOAuth2Manager = Mockery::mock(OAuth2ManagerContract::class);
            $mockOAuth2Manager->shouldReceive('fetchToken')
                ->with('facade-auth-code')
                ->andReturn($mockTokenResponse);
            
            app()->instance(OAuth2ManagerContract::class, $mockOAuth2Manager);
            
            $callbackRequest = Request::create('http://localhost/fatture-in-cloud/callback', 'GET', [
                'code' => 'facade-auth-code',
                'state' => $validState
            ]);
            
            $response = FattureInCloud::handleOAuth2Callback($callbackRequest);
            
            expect($response)->toBeInstanceOf(OAuth2TokenResponse::class);
            expect($response->getAccessToken())->toBe('facade-token-123');
        });
    });
    
    describe('Token Management Integration', function () {
        it('automatically refreshes expired tokens', function () {
            $tokenStorage = app(TokenStorageContract::class);
            $sdk = app(FattureInCloudSdk::class);
            
            // Store an expired token
            $expiredTime = time() - 3600; // 1 hour ago
            $expiredToken = new OAuth2TokenResponse('Bearer', 'expired-token', 'refresh-token-123', 3600);
            $tokenStorage->store('default', $expiredToken);
            
            expect($tokenStorage->isExpired('default'))->toBeTrue();
            
            // Mock refresh token response
            $mockRefreshResponse = new OAuth2TokenResponse('Bearer', 'refreshed-access-token', 'new-refresh-token', 3600);
            
            $mockOAuth2Manager = Mockery::mock(OAuth2ManagerContract::class);
            $mockOAuth2Manager->shouldReceive('refreshToken')
                ->with('refresh-token-123')
                ->andReturn($mockRefreshResponse);
            
            app()->instance(OAuth2ManagerContract::class, $mockOAuth2Manager);
            
            // Refresh the token
            $refreshResult = $sdk->refreshToken();
            
            expect($refreshResult)->toBeInstanceOf(OAuth2TokenResponse::class);
            expect($refreshResult->getAccessToken())->toBe('refreshed-access-token');
            expect($tokenStorage->getAccessToken('default'))->toBe('refreshed-access-token');
        });
        
        it('handles refresh token failure', function () {
            $tokenStorage = app(TokenStorageContract::class);
            $sdk = app(FattureInCloudSdk::class);
            
            // Store an expired token
            $expiredTime = time() - 3600;
            $tokenStorage->storeTokens('default', 'expired-token', 'invalid-refresh-token', $expiredTime);
            
            // Mock refresh token error
            $mockOAuth2Error = new OAuth2Error([
                'error' => 'invalid_grant',
                'error_description' => 'The refresh token is invalid or expired'
            ]);
            
            $mockOAuth2Manager = Mockery::mock(OAuth2ManagerContract::class);
            $mockOAuth2Manager->shouldReceive('refreshToken')
                ->with('invalid-refresh-token')
                ->andReturn($mockOAuth2Error);
            
            app()->instance(OAuth2ManagerContract::class, $mockOAuth2Manager);
            
            $refreshResult = $sdk->refreshToken();
            
            expect($refreshResult)->toBeNull();
            expect($tokenStorage->getAccessToken('default'))->toBeNull();
        });
        
        it('clears tokens when refresh fails', function () {
            $tokenStorage = app(TokenStorageContract::class);
            $sdk = app(FattureInCloudSdk::class);
            
            // Store tokens first
            $tokenStorage->storeTokens('default', 'access-token', 'refresh-token', time() - 3600);
            
            expect($tokenStorage->getAccessToken('default'))->toBe('access-token');
            
            // Mock OAuth2Manager to throw exception
            $mockOAuth2Manager = Mockery::mock(OAuth2ManagerContract::class);
            $mockOAuth2Manager->shouldReceive('refreshToken')
                ->andThrow(new \Exception('Network error'));
            
            app()->instance(OAuth2ManagerContract::class, $mockOAuth2Manager);
            
            $refreshResult = $sdk->refreshToken();
            
            expect($refreshResult)->toBeNull();
            expect($tokenStorage->getAccessToken('default'))->toBeNull();
            expect($tokenStorage->getRefreshToken('default'))->toBeNull();
        });
    });
    
    describe('Scopes and Permissions', function () {
        it('generates authorization URL with multiple scopes', function () {
            $sdk = app(FattureInCloudSdk::class);
            $oauth2Manager = $sdk->auth();
            
            $scopes = [
                Scope::ENTITY_CLIENTS_ALL,
                Scope::ENTITY_SUPPLIERS_READ,
                Scope::ISSUED_DOCUMENTS_INVOICES_ALL,
                Scope::PRODUCTS_ALL
            ];
            
            $authUrl = $oauth2Manager->getAuthorizationUrl($scopes);
            
            expect($authUrl)->toContain('scope=');
            expect($authUrl)->toContain('entity.clients%3Aa');
            expect($authUrl)->toContain('entity.suppliers%3Ar');
            expect($authUrl)->toContain('issued_documents.invoices%3Aa');
            expect($authUrl)->toContain('products%3Aa');
        });
        
        it('handles single scope authorization', function () {
            $sdk = app(FattureInCloudSdk::class);
            $oauth2Manager = $sdk->auth();
            
            $authUrl = $oauth2Manager->getAuthorizationUrl([Scope::ENTITY_CLIENTS_READ]);
            
            expect($authUrl)->toContain('scope=entity.clients%3Ar');
        });
        
        it('handles empty scopes array', function () {
            $sdk = app(FattureInCloudSdk::class);
            $oauth2Manager = $sdk->auth();
            
            $authUrl = $oauth2Manager->getAuthorizationUrl([]);
            
            expect($authUrl)->toContain('scope=');
        });
    });
    
    describe('State Management', function () {
        it('generates unique states for each authorization request', function () {
            $sdk = app(FattureInCloudSdk::class);
            $oauth2Manager = $sdk->auth();
            
            $authUrl1 = $oauth2Manager->getAuthorizationUrl([Scope::ENTITY_CLIENTS_READ]);
            $authUrl2 = $oauth2Manager->getAuthorizationUrl([Scope::ENTITY_CLIENTS_READ]);
            
            parse_str(parse_url($authUrl1, PHP_URL_QUERY), $params1);
            parse_str(parse_url($authUrl2, PHP_URL_QUERY), $params2);
            
            expect($params1['state'])->not->toBe($params2['state']);
        });
        
        it('validates states correctly', function () {
            $stateManager = app(StateManagerContract::class);
            
            $state1 = $stateManager->generateState();
            $state2 = $stateManager->generateState();
            
            expect($stateManager->validateState($state1))->toBeTrue();
            expect($stateManager->validateState($state2))->toBeTrue();
            expect($stateManager->validateState('invalid-state'))->toBeFalse();
        });
        
        it('expires old states', function () {
            $stateManager = app(StateManagerContract::class);
            
            $state = $stateManager->generateState();
            $stateManager->store($state);
            expect($stateManager->validate($state))->toBeTrue();
            
            // Clear the state manually to simulate expiration
            $stateManager->clear();
            expect($stateManager->validate($state))->toBeFalse();
        });
    });
    
    describe('Error Scenarios', function () {
        it('handles network failures during token exchange', function () {
            $sdk = app(FattureInCloudSdk::class);
            $stateManager = app(StateManagerContract::class);
            $validState = $stateManager->generateState();
            
            // Mock OAuth2Manager to simulate network failure
            $mockOAuth2Manager = Mockery::mock(OAuth2ManagerContract::class);
            $mockOAuth2Manager->shouldReceive('fetchToken')
                ->andThrow(new \Exception('Connection timeout'));
            
            app()->instance(OAuth2ManagerContract::class, $mockOAuth2Manager);
            
            expect(function () use ($sdk, $validState) {
                $sdk->handleCallback(
                    Request::create('http://localhost/fatture-in-cloud/callback', 'GET', [
                        'code' => 'test-code',
                        'state' => $validState
                    ])
                );
            })->toThrow(\Exception::class, 'Connection timeout');
        });
        
        it('handles malformed OAuth2 responses', function () {
            $sdk = app(FattureInCloudSdk::class);
            $stateManager = app(StateManagerContract::class);
            $validState = $stateManager->generateState();
            
            // Mock OAuth2Manager to return an error
            $mockOAuth2Error = new OAuth2Error([
                'error' => 'invalid_request',
                'error_description' => 'The request is missing a required parameter'
            ]);
            
            $mockOAuth2Manager = Mockery::mock(OAuth2ManagerContract::class);
            $mockOAuth2Manager->shouldReceive('fetchToken')
                ->andReturn($mockOAuth2Error);
            
            app()->instance(OAuth2ManagerContract::class, $mockOAuth2Manager);
            
            $result = $sdk->handleCallback(
                Request::create('http://localhost/fatture-in-cloud/callback', 'GET', [
                    'code' => 'test-code',
                    'state' => $validState
                ])
            );
            
            expect($result)->toBeInstanceOf(OAuth2Error::class);
            expect($result->getError())->toBe('invalid_request');
        });
    });
    
    describe('Configuration Edge Cases', function () {
        it('handles missing OAuth2 configuration gracefully', function () {
            config()->set('fatture-in-cloud.client_id', null);
            config()->set('fatture-in-cloud.client_secret', null);
            
            $sdk = app(FattureInCloudSdk::class);
            
            expect(function () use ($sdk) {
                $sdk->getAuthorizationUrl([Scope::ENTITY_CLIENTS_READ]);
            })->toThrow(\Codeman\FattureInCloud\Exceptions\OAuth2Exception::class);
        });
        
        it('uses manual authentication when access_token is configured', function () {
            config()->set('fatture-in-cloud.access_token', 'manual-token-123');
            
            $sdk = app(FattureInCloudSdk::class);
            
            // API services should use the manual token
            $companiesApi = $sdk->companies();
            expect($companiesApi->getConfig()->getAccessToken())->toBe('manual-token-123');
        });
        
        it('handles missing redirect URL configuration', function () {
            config()->set('fatture-in-cloud.redirect_url', null);
            
            // Should still work using route generation
            $sdk = app(FattureInCloudSdk::class);
            
            expect(function () use ($sdk) {
                $authUrl = $sdk->getAuthorizationUrl();
                expect($authUrl)->toContain('redirect_uri=');
            })->not->toThrow(\Exception::class);
        });
    });
});