<?php

use Codeman\FattureInCloud\Contracts\ApiServiceFactory;
use Codeman\FattureInCloud\Contracts\OAuth2Manager;
use Codeman\FattureInCloud\Contracts\TokenStorage;
use Codeman\FattureInCloud\FattureInCloudSdk;
use FattureInCloud\OAuth2\OAuth2TokenResponse;
use FattureInCloud\OAuth2\Scope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

describe('OAuth2 Helper Methods', function () {
    beforeEach(function () {
        // Mock dependencies
        $this->oauthManager = Mockery::mock(OAuth2Manager::class);
        $this->tokenStorage = Mockery::mock(TokenStorage::class);
        $this->apiFactory = Mockery::mock(ApiServiceFactory::class);

        // Create SDK instance with mocked dependencies
        $this->sdk = new FattureInCloudSdk(
            $this->oauthManager,
            $this->tokenStorage,
            $this->apiFactory,
            'test-context'
        );
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('redirectToAuthorization method', function () {
        test('returns RedirectResponse when OAuth2 manager is initialized', function () {
            // Arrange
            $scopes = [Scope::ENTITY_CLIENTS_READ, Scope::ISSUED_DOCUMENTS_INVOICES_READ];
            $authUrl = 'https://api-v2.fattureincloud.it/oauth/authorize?client_id=test&scopes='.urlencode(implode(' ', $scopes)).'&state=random-state';

            $this->oauthManager
                ->shouldReceive('isInitialized')
                ->once()
                ->andReturn(true);

            $this->oauthManager
                ->shouldReceive('getAuthorizationUrl')
                ->once()
                ->with($scopes, null)
                ->andReturn($authUrl);

            // Act
            $response = $this->sdk->redirectToAuthorization($scopes);

            // Assert
            expect($response)->toBeInstanceOf(RedirectResponse::class);
            expect($response->getTargetUrl())->toBe($authUrl);
        });

        test('throws LogicException when OAuth2 manager is not initialized', function () {
            // Arrange
            $scopes = [Scope::ENTITY_CLIENTS_READ];

            $this->oauthManager
                ->shouldReceive('isInitialized')
                ->once()
                ->andReturn(false);

            // Act & Assert
            expect(fn () => $this->sdk->redirectToAuthorization($scopes))
                ->toThrow(
                    \LogicException::class,
                    'OAuth2 manager is not initialized. Please ensure FATTUREINCLOUD_CLIENT_ID and FATTUREINCLOUD_CLIENT_SECRET are configured.'
                );
        });

        test('works with empty scopes array', function () {
            // Arrange
            $scopes = [];
            $authUrl = 'https://api-v2.fattureincloud.it/oauth/authorize?client_id=test&scopes=&state=random-state';

            $this->oauthManager
                ->shouldReceive('isInitialized')
                ->once()
                ->andReturn(true);

            $this->oauthManager
                ->shouldReceive('getAuthorizationUrl')
                ->once()
                ->with($scopes, null)
                ->andReturn($authUrl);

            // Act
            $response = $this->sdk->redirectToAuthorization($scopes);

            // Assert
            expect($response)->toBeInstanceOf(RedirectResponse::class);
        });

        test('works with multiple scopes', function () {
            // Arrange
            $scopes = [
                Scope::ENTITY_CLIENTS_READ,
                Scope::ISSUED_DOCUMENTS_INVOICES_ALL,
                Scope::ENTITY_SUPPLIERS_READ,
                Scope::PRODUCTS_READ,
            ];
            $authUrl = 'https://api-v2.fattureincloud.it/oauth/authorize?client_id=test&scopes='.urlencode(implode(' ', $scopes)).'&state=random-state';

            $this->oauthManager
                ->shouldReceive('isInitialized')
                ->once()
                ->andReturn(true);

            $this->oauthManager
                ->shouldReceive('getAuthorizationUrl')
                ->once()
                ->with($scopes, null)
                ->andReturn($authUrl);

            // Act
            $response = $this->sdk->redirectToAuthorization($scopes);

            // Assert
            expect($response)->toBeInstanceOf(RedirectResponse::class);
            expect($response->getTargetUrl())->toBe($authUrl);
        });
    });

    describe('handleOAuth2Callback method', function () {
        test('successfully handles valid callback with code and state', function () {
            // Arrange
            $code = 'auth-code-123';
            $state = 'state-456';
            $tokenResponse = Mockery::mock(OAuth2TokenResponse::class);

            $request = new Request([
                'code' => $code,
                'state' => $state,
            ]);

            $this->oauthManager
                ->shouldReceive('fetchToken')
                ->once()
                ->with($code, $state)
                ->andReturn($tokenResponse);

            $this->tokenStorage
                ->shouldReceive('store')
                ->once()
                ->with('test-context', $tokenResponse);

            // Act
            $result = $this->sdk->handleOAuth2Callback($request);

            // Assert
            expect($result)->toBe($tokenResponse);
        });

        test('throws InvalidArgumentException when OAuth2 error is present', function () {
            // Arrange
            $request = new Request([
                'error' => 'access_denied',
                'error_description' => 'User denied authorization',
            ]);

            // Act & Assert
            expect(fn () => $this->sdk->handleOAuth2Callback($request))
                ->toThrow(
                    \InvalidArgumentException::class,
                    'OAuth2 authorization failed: access_denied - User denied authorization'
                );
        });

        test('throws InvalidArgumentException when OAuth2 error is present without description', function () {
            // Arrange
            $request = new Request([
                'error' => 'invalid_request',
            ]);

            // Act & Assert
            expect(fn () => $this->sdk->handleOAuth2Callback($request))
                ->toThrow(
                    \InvalidArgumentException::class,
                    'OAuth2 authorization failed: invalid_request'
                );
        });

        test('throws InvalidArgumentException when code is missing', function () {
            // Arrange
            $request = new Request([
                'state' => 'state-456',
            ]);

            // Act & Assert
            expect(fn () => $this->sdk->handleOAuth2Callback($request))
                ->toThrow(
                    \InvalidArgumentException::class,
                    'Missing required OAuth2 callback parameters: code and state are required'
                );
        });

        test('throws InvalidArgumentException when state is missing', function () {
            // Arrange
            $request = new Request([
                'code' => 'auth-code-123',
            ]);

            // Act & Assert
            expect(fn () => $this->sdk->handleOAuth2Callback($request))
                ->toThrow(
                    \InvalidArgumentException::class,
                    'Missing required OAuth2 callback parameters: code and state are required'
                );
        });

        test('throws InvalidArgumentException when both code and state are missing', function () {
            // Arrange
            $request = new Request([]);

            // Act & Assert
            expect(fn () => $this->sdk->handleOAuth2Callback($request))
                ->toThrow(
                    \InvalidArgumentException::class,
                    'Missing required OAuth2 callback parameters: code and state are required'
                );
        });

        test('handles common OAuth2 error scenarios', function () {
            $errorScenarios = [
                ['access_denied', 'The user denied authorization'],
                ['invalid_request', 'Invalid request parameters'],
                ['invalid_client', 'Client authentication failed'],
                ['invalid_grant', 'Authorization grant is invalid'],
                ['unsupported_response_type', 'Response type not supported'],
                ['invalid_scope', 'Requested scope is invalid'],
                ['server_error', 'Server encountered an error'],
                ['temporarily_unavailable', 'Service temporarily unavailable'],
            ];

            foreach ($errorScenarios as [$error, $description]) {
                $request = new Request([
                    'error' => $error,
                    'error_description' => $description,
                ]);

                expect(fn () => $this->sdk->handleOAuth2Callback($request))
                    ->toThrow(
                        \InvalidArgumentException::class,
                        "OAuth2 authorization failed: {$error} - {$description}"
                    );
            }
        });

        test('delegates to existing fetchToken method for token exchange', function () {
            // Arrange
            $code = 'auth-code-123';
            $state = 'state-456';
            $tokenResponse = Mockery::mock(OAuth2TokenResponse::class);

            $request = new Request([
                'code' => $code,
                'state' => $state,
            ]);

            // Create a partial mock that allows us to verify fetchToken is called
            $sdkMock = Mockery::mock(FattureInCloudSdk::class, [
                $this->oauthManager,
                $this->tokenStorage,
                $this->apiFactory,
                'test-context',
            ])->makePartial();

            $sdkMock
                ->shouldReceive('fetchToken')
                ->once()
                ->with($code, $state)
                ->andReturn($tokenResponse);

            // Act
            $result = $sdkMock->handleOAuth2Callback($request);

            // Assert
            expect($result)->toBe($tokenResponse);
        });

        test('handles empty string parameters as missing', function () {
            // Arrange
            $request = new Request([
                'code' => '',
                'state' => '',
            ]);

            // Act & Assert
            expect(fn () => $this->sdk->handleOAuth2Callback($request))
                ->toThrow(
                    \InvalidArgumentException::class,
                    'Missing required OAuth2 callback parameters: code and state are required'
                );
        });

        test('handles null parameters as missing', function () {
            // Arrange
            $request = new Request([
                'code' => null,
                'state' => null,
            ]);

            // Act & Assert
            expect(fn () => $this->sdk->handleOAuth2Callback($request))
                ->toThrow(
                    \InvalidArgumentException::class,
                    'Missing required OAuth2 callback parameters: code and state are required'
                );
        });
    });

    describe('Integration with existing methods', function () {
        test('redirectToAuthorization uses same OAuth2Manager as getAuthorizationUrl', function () {
            // Arrange
            $scopes = [Scope::ENTITY_CLIENTS_READ];
            $authUrl = 'https://api-v2.fattureincloud.it/oauth/authorize?test';

            $this->oauthManager
                ->shouldReceive('isInitialized')
                ->once()
                ->andReturn(true);

            $this->oauthManager
                ->shouldReceive('getAuthorizationUrl')
                ->twice() // Called once by redirectToAuthorization, once by getAuthorizationUrl
                ->with($scopes, null) // getAuthorizationUrl has a default null state parameter
                ->andReturn($authUrl);

            // Act
            $redirectResponse = $this->sdk->redirectToAuthorization($scopes);
            $directUrl = $this->sdk->getAuthorizationUrl($scopes);

            // Assert - both should use the same underlying OAuth2Manager
            expect($redirectResponse->getTargetUrl())->toBe($authUrl);
            expect($directUrl)->toBe($authUrl);
        });

        test('handleOAuth2Callback uses same fetchToken method', function () {
            // Arrange
            $code = 'auth-code-123';
            $state = 'state-456';
            $tokenResponse = Mockery::mock(OAuth2TokenResponse::class);

            $request = new Request([
                'code' => $code,
                'state' => $state,
            ]);

            $this->oauthManager
                ->shouldReceive('fetchToken')
                ->twice() // Once for handleOAuth2Callback, once for direct fetchToken
                ->with($code, $state)
                ->andReturn($tokenResponse);

            $this->tokenStorage
                ->shouldReceive('store')
                ->twice()
                ->with('test-context', $tokenResponse);

            // Act
            $callbackResult = $this->sdk->handleOAuth2Callback($request);
            $directResult = $this->sdk->fetchToken($code, $state);

            // Assert - both should return the same token response
            expect($callbackResult)->toBe($tokenResponse);
            expect($directResult)->toBe($tokenResponse);
        });
    });

    describe('Error propagation', function () {
        test('redirectToAuthorization propagates OAuth2Manager exceptions', function () {
            // Arrange
            $scopes = [Scope::ENTITY_CLIENTS_READ];

            $this->oauthManager
                ->shouldReceive('isInitialized')
                ->once()
                ->andReturn(true);

            $this->oauthManager
                ->shouldReceive('getAuthorizationUrl')
                ->once()
                ->with($scopes, null)
                ->andThrow(new \InvalidArgumentException('Invalid scope'));

            // Act & Assert
            expect(fn () => $this->sdk->redirectToAuthorization($scopes))
                ->toThrow(\InvalidArgumentException::class, 'Invalid scope');
        });

        test('handleOAuth2Callback propagates fetchToken exceptions', function () {
            // Arrange
            $code = 'invalid-code';
            $state = 'invalid-state';

            $request = new Request([
                'code' => $code,
                'state' => $state,
            ]);

            $this->oauthManager
                ->shouldReceive('fetchToken')
                ->once()
                ->with($code, $state)
                ->andThrow(new \InvalidArgumentException('Invalid state parameter'));

            // Don't expect tokenStorage to be called since fetchToken throws
            $this->tokenStorage->shouldNotReceive('store');

            // Act & Assert
            expect(fn () => $this->sdk->handleOAuth2Callback($request))
                ->toThrow(\InvalidArgumentException::class, 'Invalid state parameter');
        });
    });
});
