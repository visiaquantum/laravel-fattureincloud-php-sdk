<?php

use Codeman\FattureInCloud\Contracts\OAuth2Manager as OAuth2ManagerContract;
use Codeman\FattureInCloud\Contracts\TokenStorage as TokenStorageContract;
use Codeman\FattureInCloud\Controllers\OAuth2CallbackController;
use Codeman\FattureInCloud\Exceptions\TokenExchangeException;
use Codeman\FattureInCloud\Services\OAuth2ErrorHandler;
use FattureInCloud\OAuth2\OAuth2TokenResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;

describe('OAuth2CallbackController', function () {
    beforeEach(function () {
        $this->oauth2Manager = Mockery::mock(OAuth2ManagerContract::class);
        $this->tokenStorage = Mockery::mock(TokenStorageContract::class);
        $this->errorHandler = Mockery::mock(OAuth2ErrorHandler::class);

        $this->controller = new OAuth2CallbackController(
            $this->oauth2Manager,
            $this->tokenStorage,
            $this->errorHandler
        );
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('__invoke method', function () {
        describe('when OAuth2 error parameters are present', function () {
            it('handles access_denied error correctly', function () {
                // Arrange
                $request = Request::create('/callback', 'GET', [
                    'error' => 'access_denied',
                    'error_description' => 'User denied authorization',
                ]);

                $expectedResponse = new Response(json_encode([
                    'status' => 'error',
                    'message' => 'Authorization was denied by the user: User denied authorization',
                ]), 400, ['Content-Type' => 'application/json']);

                $this->errorHandler->shouldReceive('handleCallbackError')
                    ->once()
                    ->with($request)
                    ->andReturn($expectedResponse);

                // Act
                $response = $this->controller->__invoke($request);

                // Assert
                expect($response)->toBeInstanceOf(Response::class);
                expect($response->getStatusCode())->toBe(400);
                expect($response->getContent())->toBeJson();

                $content = json_decode($response->getContent(), true);
                expect($content['status'])->toBe('error');
                expect($content['message'])->toContain('Authorization was denied by the user');
                expect($content['message'])->toContain('User denied authorization');
            });

            it('handles invalid_request error correctly', function () {
                // Arrange
                $request = Request::create('/callback', 'GET', [
                    'error' => 'invalid_request',
                    'error_description' => 'Missing required parameter',
                ]);

                $expectedResponse = new Response(json_encode([
                    'status' => 'error',
                    'message' => 'The request is missing a required parameter: Missing required parameter',
                ]), 400, ['Content-Type' => 'application/json']);

                $this->errorHandler->shouldReceive('handleCallbackError')
                    ->once()
                    ->with($request)
                    ->andReturn($expectedResponse);

                // Act
                $response = $this->controller->__invoke($request);

                // Assert
                expect($response->getStatusCode())->toBe(400);
                $content = json_decode($response->getContent(), true);
                expect($content['status'])->toBe('error');
                expect($content['message'])->toContain('The request is missing a required parameter');
                expect($content['message'])->toContain('Missing required parameter');
            });

            it('handles unauthorized_client error correctly', function () {
                // Arrange
                $request = Request::create('/callback', 'GET', [
                    'error' => 'unauthorized_client',
                ]);

                $expectedResponse = new Response(json_encode([
                    'status' => 'error',
                    'message' => 'The client is not authorized: No description provided',
                ]), 400, ['Content-Type' => 'application/json']);

                $this->errorHandler->shouldReceive('handleCallbackError')
                    ->once()
                    ->with($request)
                    ->andReturn($expectedResponse);

                // Act
                $response = $this->controller->__invoke($request);

                // Assert
                expect($response->getStatusCode())->toBe(400);
                $content = json_decode($response->getContent(), true);
                expect($content['message'])->toContain('The client is not authorized');
            });

            it('handles unsupported_response_type error correctly', function () {
                // Arrange
                $request = Request::create('/callback', 'GET', [
                    'error' => 'unsupported_response_type',
                ]);

                $expectedResponse = new Response(json_encode([
                    'status' => 'error',
                    'message' => 'The authorization server does not support: No description provided',
                ]), 400, ['Content-Type' => 'application/json']);

                $this->errorHandler->shouldReceive('handleCallbackError')
                    ->once()
                    ->with($request)
                    ->andReturn($expectedResponse);

                // Act
                $response = $this->controller->__invoke($request);

                // Assert
                expect($response->getStatusCode())->toBe(400);
                $content = json_decode($response->getContent(), true);
                expect($content['message'])->toContain('The authorization server does not support');
            });

            it('handles invalid_scope error correctly', function () {
                // Arrange
                $request = Request::create('/callback', 'GET', [
                    'error' => 'invalid_scope',
                ]);

                $expectedResponse = new Response(json_encode([
                    'status' => 'error',
                    'message' => 'The requested scope is invalid: No description provided',
                ]), 400, ['Content-Type' => 'application/json']);

                $this->errorHandler->shouldReceive('handleCallbackError')
                    ->once()
                    ->with($request)
                    ->andReturn($expectedResponse);

                // Act
                $response = $this->controller->__invoke($request);

                // Assert
                expect($response->getStatusCode())->toBe(400);
                $content = json_decode($response->getContent(), true);
                expect($content['message'])->toContain('The requested scope is invalid');
            });

            it('handles server_error correctly', function () {
                // Arrange
                $request = Request::create('/callback', 'GET', [
                    'error' => 'server_error',
                ]);

                $expectedResponse = new Response(json_encode([
                    'status' => 'error',
                    'message' => 'The authorization server encountered: No description provided',
                ]), 400, ['Content-Type' => 'application/json']);

                $this->errorHandler->shouldReceive('handleCallbackError')
                    ->once()
                    ->with($request)
                    ->andReturn($expectedResponse);

                // Act
                $response = $this->controller->__invoke($request);

                // Assert
                expect($response->getStatusCode())->toBe(400);
                $content = json_decode($response->getContent(), true);
                expect($content['message'])->toContain('The authorization server encountered');
            });

            it('handles temporarily_unavailable error correctly', function () {
                // Arrange
                $request = Request::create('/callback', 'GET', [
                    'error' => 'temporarily_unavailable',
                ]);

                $expectedResponse = new Response(json_encode([
                    'status' => 'error',
                    'message' => 'temporarily overloaded: No description provided',
                ]), 400, ['Content-Type' => 'application/json']);

                $this->errorHandler->shouldReceive('handleCallbackError')
                    ->once()
                    ->with($request)
                    ->andReturn($expectedResponse);

                // Act
                $response = $this->controller->__invoke($request);

                // Assert
                expect($response->getStatusCode())->toBe(400);
                $content = json_decode($response->getContent(), true);
                expect($content['message'])->toContain('temporarily overloaded');
            });

            it('handles unknown OAuth2 error with default message', function () {
                // Arrange
                $request = Request::create('/callback', 'GET', [
                    'error' => 'unknown_error',
                    'error_description' => 'Something went wrong',
                ]);

                $expectedResponse = new Response(json_encode([
                    'status' => 'error',
                    'message' => 'Unknown OAuth2 error: unknown_error. Something went wrong',
                ]), 400, ['Content-Type' => 'application/json']);

                $this->errorHandler->shouldReceive('handleCallbackError')
                    ->once()
                    ->with($request)
                    ->andReturn($expectedResponse);

                // Act
                $response = $this->controller->__invoke($request);

                // Assert
                expect($response->getStatusCode())->toBe(400);
                $content = json_decode($response->getContent(), true);
                expect($content['message'])->toContain('OAuth2 error: unknown_error');
                expect($content['message'])->toContain('Something went wrong');
            });

            it('handles OAuth2 error without description', function () {
                // Arrange
                $request = Request::create('/callback', 'GET', [
                    'error' => 'access_denied',
                ]);

                $expectedResponse = new Response(json_encode([
                    'status' => 'error',
                    'message' => 'Authorization was denied by the user: No description provided',
                ]), 400, ['Content-Type' => 'application/json']);

                $this->errorHandler->shouldReceive('handleCallbackError')
                    ->once()
                    ->with($request)
                    ->andReturn($expectedResponse);

                // Act
                $response = $this->controller->__invoke($request);

                // Assert
                expect($response->getStatusCode())->toBe(400);
                $content = json_decode($response->getContent(), true);
                expect($content['message'])->toContain('No description provided');
            });
        });

        describe('when required parameters are missing', function () {
            it('returns error when code parameter is missing', function () {
                // Arrange
                $request = Request::create('/callback', 'GET', [
                    'state' => 'valid-state',
                ]);

                $expectedResponse = new Response(json_encode([
                    'status' => 'error',
                    'message' => 'Missing required parameters: code and state are required',
                ]), 400, ['Content-Type' => 'application/json']);

                $this->errorHandler->shouldReceive('createErrorResponse')
                    ->once()
                    ->andReturn($expectedResponse);

                // Act
                $response = $this->controller->__invoke($request);

                // Assert
                expect($response->getStatusCode())->toBe(400);
                $content = json_decode($response->getContent(), true);
                expect($content['status'])->toBe('error');
                expect($content['message'])->toBe('Missing required parameters: code and state are required');
            });

            it('returns error when state parameter is missing', function () {
                // Arrange
                $request = Request::create('/callback', 'GET', [
                    'code' => 'auth-code',
                ]);

                $expectedResponse = new Response(json_encode([
                    'status' => 'error',
                    'message' => 'Missing required parameters: code and state are required',
                ]), 400, ['Content-Type' => 'application/json']);

                $this->errorHandler->shouldReceive('createErrorResponse')
                    ->once()
                    ->andReturn($expectedResponse);

                // Act
                $response = $this->controller->__invoke($request);

                // Assert
                expect($response->getStatusCode())->toBe(400);
                $content = json_decode($response->getContent(), true);
                expect($content['status'])->toBe('error');
                expect($content['message'])->toBe('Missing required parameters: code and state are required');
            });

            it('returns error when both code and state parameters are missing', function () {
                // Arrange
                $request = Request::create('/callback', 'GET');

                $expectedResponse = new Response(json_encode([
                    'status' => 'error',
                    'message' => 'Missing required parameters: code and state are required',
                ]), 400, ['Content-Type' => 'application/json']);

                $this->errorHandler->shouldReceive('createErrorResponse')
                    ->once()
                    ->andReturn($expectedResponse);

                // Act
                $response = $this->controller->__invoke($request);

                // Assert
                expect($response->getStatusCode())->toBe(400);
                $content = json_decode($response->getContent(), true);
                expect($content['status'])->toBe('error');
                expect($content['message'])->toBe('Missing required parameters: code and state are required');
            });
        });

        describe('when token exchange succeeds', function () {
            it('successfully exchanges code for tokens with refresh token and stores them', function () {
                // Arrange
                $request = Request::create('/callback', 'GET', [
                    'code' => 'auth-code',
                    'state' => 'csrf-state',
                ]);

                $tokenResponse = new OAuth2TokenResponse(
                    'Bearer',
                    'access-token-123',
                    'refresh-token-456',
                    3600
                );

                $expectedResponse = new Response(json_encode([
                    'status' => 'success',
                    'message' => 'OAuth2 authorization completed successfully',
                    'data' => [
                        'token_type' => 'Bearer',
                        'expires_in' => 3600,
                        'has_refresh_token' => true,
                    ],
                ]), 200, ['Content-Type' => 'application/json']);

                // Mock OAuth2Manager to return successful token response
                $this->oauth2Manager->shouldReceive('fetchToken')
                    ->once()
                    ->with('auth-code', 'csrf-state')
                    ->andReturn($tokenResponse);

                // Mock TokenStorage to store the tokens
                $this->tokenStorage->shouldReceive('store')
                    ->once()
                    ->with('default', $tokenResponse);

                // Mock errorHandler to create success response
                $this->errorHandler->shouldReceive('createSuccessResponse')
                    ->once()
                    ->with([
                        'token_type' => 'Bearer',
                        'expires_in' => 3600,
                        'has_refresh_token' => true,
                    ], 'OAuth2 authorization completed successfully')
                    ->andReturn($expectedResponse);

                // Act
                $response = $this->controller->__invoke($request);

                // Assert
                expect($response->getStatusCode())->toBe(200);
                expect($response->headers->get('Content-Type'))->toBe('application/json');

                $content = json_decode($response->getContent(), true);
                expect($content['status'])->toBe('success');
                expect($content['message'])->toBe('OAuth2 authorization completed successfully');
                expect($content['data']['token_type'])->toBe('Bearer');
                expect($content['data']['expires_in'])->toBe(3600);
                expect($content['data']['has_refresh_token'])->toBeTrue();
            });

            it('successfully exchanges code for tokens without refresh token', function () {
                // Arrange
                $request = Request::create('/callback', 'GET', [
                    'code' => 'auth-code',
                    'state' => 'csrf-state',
                ]);

                $tokenResponse = new OAuth2TokenResponse(
                    'Bearer',
                    'access-token-123',
                    '', // empty refresh token
                    7200
                );

                $expectedResponse = new Response(json_encode([
                    'status' => 'success',
                    'message' => 'OAuth2 authorization completed successfully',
                    'data' => [
                        'token_type' => 'Bearer',
                        'expires_in' => 7200,
                        'has_refresh_token' => false,
                    ],
                ]), 200, ['Content-Type' => 'application/json']);

                $this->oauth2Manager->shouldReceive('fetchToken')
                    ->once()
                    ->with('auth-code', 'csrf-state')
                    ->andReturn($tokenResponse);

                $this->tokenStorage->shouldReceive('store')
                    ->once()
                    ->with('default', $tokenResponse);

                $this->errorHandler->shouldReceive('createSuccessResponse')
                    ->once()
                    ->with([
                        'token_type' => 'Bearer',
                        'expires_in' => 7200,
                        'has_refresh_token' => false,
                    ], 'OAuth2 authorization completed successfully')
                    ->andReturn($expectedResponse);

                // Act
                $response = $this->controller->__invoke($request);

                // Assert
                expect($response->getStatusCode())->toBe(200);
                $content = json_decode($response->getContent(), true);
                expect($content['status'])->toBe('success');
                expect($content['data']['has_refresh_token'])->toBeFalse();
                expect($content['data']['expires_in'])->toBe(7200);
            });
        });

        describe('when token exchange fails with exceptions', function () {
            it('handles OAuth2Exception correctly', function () {
                // Arrange
                $request = Request::create('/callback', 'GET', [
                    'code' => 'invalid-code',
                    'state' => 'csrf-state',
                ]);

                $oauth2Exception = TokenExchangeException::invalidCode('Invalid authorization code');

                $expectedResponse = new Response(json_encode([
                    'status' => 'error',
                    'message' => 'OAuth2 error: Invalid authorization code',
                ]), 400, ['Content-Type' => 'application/json']);

                $this->oauth2Manager->shouldReceive('fetchToken')
                    ->once()
                    ->with('invalid-code', 'csrf-state')
                    ->andThrow($oauth2Exception);

                $this->errorHandler->shouldReceive('handleException')
                    ->once()
                    ->with($oauth2Exception)
                    ->andReturn($expectedResponse);

                // Act
                $response = $this->controller->__invoke($request);

                // Assert
                expect($response->getStatusCode())->toBe(400);
                $content = json_decode($response->getContent(), true);
                expect($content['status'])->toBe('error');
                expect($content['message'])->toBe('OAuth2 error: Invalid authorization code');
            });

            it('handles InvalidArgumentException for invalid state', function () {
                // Arrange
                $request = Request::create('/callback', 'GET', [
                    'code' => 'auth-code',
                    'state' => 'invalid-state',
                ]);

                $invalidArgumentException = new \InvalidArgumentException('State parameter is invalid or expired');

                $expectedResponse = new Response(json_encode([
                    'status' => 'error',
                    'message' => 'Invalid state parameter: State parameter is invalid or expired',
                ]), 400, ['Content-Type' => 'application/json']);

                $this->oauth2Manager->shouldReceive('fetchToken')
                    ->once()
                    ->with('auth-code', 'invalid-state')
                    ->andThrow($invalidArgumentException);

                $this->errorHandler->shouldReceive('handleException')
                    ->once()
                    ->with($invalidArgumentException)
                    ->andReturn($expectedResponse);

                // Act
                $response = $this->controller->__invoke($request);

                // Assert
                expect($response->getStatusCode())->toBe(400);
                $content = json_decode($response->getContent(), true);
                expect($content['status'])->toBe('error');
                expect($content['message'])->toBe('Invalid state parameter: State parameter is invalid or expired');
            });

            it('handles LogicException for configuration errors', function () {
                // Arrange
                $request = Request::create('/callback', 'GET', [
                    'code' => 'auth-code',
                    'state' => 'csrf-state',
                ]);

                $logicException = new \LogicException('OAuth2 client not properly configured');

                $expectedResponse = new Response(json_encode([
                    'status' => 'error',
                    'message' => 'Configuration error: OAuth2 client not properly configured',
                ]), 500, ['Content-Type' => 'application/json']);

                $this->oauth2Manager->shouldReceive('fetchToken')
                    ->once()
                    ->with('auth-code', 'csrf-state')
                    ->andThrow($logicException);

                $this->errorHandler->shouldReceive('handleException')
                    ->once()
                    ->with($logicException)
                    ->andReturn($expectedResponse);

                // Act
                $response = $this->controller->__invoke($request);

                // Assert
                expect($response->getStatusCode())->toBe(500);
                $content = json_decode($response->getContent(), true);
                expect($content['status'])->toBe('error');
                expect($content['message'])->toBe('Configuration error: OAuth2 client not properly configured');
            });

            it('handles generic Exception as unexpected error', function () {
                // Arrange
                $request = Request::create('/callback', 'GET', [
                    'code' => 'auth-code',
                    'state' => 'csrf-state',
                ]);

                $genericException = new \RuntimeException('Network timeout');

                $expectedResponse = new Response(json_encode([
                    'status' => 'error',
                    'message' => 'An unexpected error occurred during token exchange',
                ]), 500, ['Content-Type' => 'application/json']);

                $this->oauth2Manager->shouldReceive('fetchToken')
                    ->once()
                    ->with('auth-code', 'csrf-state')
                    ->andThrow($genericException);

                $this->errorHandler->shouldReceive('handleException')
                    ->once()
                    ->with($genericException)
                    ->andReturn($expectedResponse);

                // Act
                $response = $this->controller->__invoke($request);

                // Assert
                expect($response->getStatusCode())->toBe(500);
                $content = json_decode($response->getContent(), true);
                expect($content['status'])->toBe('error');
                expect($content['message'])->toBe('An unexpected error occurred during token exchange');
            });
        });

        describe('when token storage fails', function () {
            it('handles exception during token storage', function () {
                // Arrange
                $request = Request::create('/callback', 'GET', [
                    'code' => 'auth-code',
                    'state' => 'csrf-state',
                ]);

                $tokenResponse = new OAuth2TokenResponse(
                    'Bearer',
                    'access-token-123',
                    'refresh-token-456',
                    3600
                );

                $storageException = new \RuntimeException('Cache connection failed');

                $expectedResponse = new Response(json_encode([
                    'status' => 'error',
                    'message' => 'An unexpected error occurred during token exchange',
                ]), 500, ['Content-Type' => 'application/json']);

                $this->oauth2Manager->shouldReceive('fetchToken')
                    ->once()
                    ->with('auth-code', 'csrf-state')
                    ->andReturn($tokenResponse);

                // Mock token storage to throw exception
                $this->tokenStorage->shouldReceive('store')
                    ->once()
                    ->with('default', $tokenResponse)
                    ->andThrow($storageException);

                $this->errorHandler->shouldReceive('handleException')
                    ->once()
                    ->with($storageException)
                    ->andReturn($expectedResponse);

                // Act
                $response = $this->controller->__invoke($request);

                // Assert
                expect($response->getStatusCode())->toBe(500);
                $content = json_decode($response->getContent(), true);
                expect($content['status'])->toBe('error');
                expect($content['message'])->toBe('An unexpected error occurred during token exchange');
            });
        });
    });

    describe('registered route', function () {
        it('registers the callback route correctly', function () {
            // Act & Assert
            $this->get('/fatture-in-cloud/callback')
                ->assertStatus(400); // Expect 400 because no parameters provided

            // Check if route exists and has correct name
            $route = RouteFacade::getRoutes()->getByName('fatture-in-cloud.callback');
            expect($route)->toBeInstanceOf(Route::class);
            expect($route->uri())->toBe('fatture-in-cloud/callback');
            expect($route->methods())->toContain('GET');
        });

        it('handles OAuth2 callback route with valid parameters', function () {
            // Arrange - Mock the OAuth2Manager, TokenStorage, and ErrorHandler in the container
            $this->app->instance(OAuth2ManagerContract::class, $this->oauth2Manager);
            $this->app->instance(TokenStorageContract::class, $this->tokenStorage);
            $this->app->instance(OAuth2ErrorHandler::class, $this->errorHandler);

            $tokenResponse = new OAuth2TokenResponse(
                'Bearer',
                'access-token-123',
                'refresh-token-456',
                3600
            );

            $expectedResponse = new Response(json_encode([
                'status' => 'success',
                'message' => 'OAuth2 authorization completed successfully',
                'data' => [
                    'token_type' => 'Bearer',
                    'expires_in' => 3600,
                    'has_refresh_token' => true,
                ],
            ]), 200, ['Content-Type' => 'application/json']);

            $this->oauth2Manager->shouldReceive('fetchToken')
                ->once()
                ->with('auth-code', 'csrf-state')
                ->andReturn($tokenResponse);

            $this->tokenStorage->shouldReceive('store')
                ->once()
                ->with('default', $tokenResponse);

            $this->errorHandler->shouldReceive('createSuccessResponse')
                ->once()
                ->with([
                    'token_type' => 'Bearer',
                    'expires_in' => 3600,
                    'has_refresh_token' => true,
                ], 'OAuth2 authorization completed successfully')
                ->andReturn($expectedResponse);

            // Act & Assert
            $response = $this->get('/fatture-in-cloud/callback?code=auth-code&state=csrf-state');

            $response->assertStatus(200);
            $response->assertJson([
                'status' => 'success',
                'message' => 'OAuth2 authorization completed successfully',
                'data' => [
                    'token_type' => 'Bearer',
                    'expires_in' => 3600,
                    'has_refresh_token' => true,
                ],
            ]);
        });

        it('handles OAuth2 callback route with error parameters', function () {
            // Arrange - Mock the ErrorHandler in the container
            $this->app->instance(OAuth2ErrorHandler::class, $this->errorHandler);

            $expectedResponse = new Response(json_encode([
                'status' => 'error',
                'message' => 'Authorization was denied by the user: User denied',
            ]), 400, ['Content-Type' => 'application/json']);

            $this->errorHandler->shouldReceive('handleCallbackError')
                ->once()
                ->andReturn($expectedResponse);

            // Act & Assert
            $response = $this->get('/fatture-in-cloud/callback?error=access_denied&error_description=User denied');

            $response->assertStatus(400);
            $response->assertJson([
                'status' => 'error',
            ]);

            $content = $response->json();
            expect($content['message'])->toContain('Authorization was denied');
            expect($content['message'])->toContain('User denied');
        });

        it('handles OAuth2 callback route with missing parameters', function () {
            // Arrange - Mock the ErrorHandler in the container
            $this->app->instance(OAuth2ErrorHandler::class, $this->errorHandler);

            $expectedResponse = new Response(json_encode([
                'status' => 'error',
                'message' => 'Missing required parameters: code and state are required',
            ]), 400, ['Content-Type' => 'application/json']);

            $this->errorHandler->shouldReceive('createErrorResponse')
                ->once()
                ->andReturn($expectedResponse);

            // Act & Assert
            $response = $this->get('/fatture-in-cloud/callback');

            $response->assertStatus(400);
            $response->assertJson([
                'status' => 'error',
                'message' => 'Missing required parameters: code and state are required',
            ]);
        });
    });

    describe('response format and headers', function () {
        it('returns correct JSON response format for success', function () {
            // Arrange
            $request = Request::create('/callback', 'GET', [
                'code' => 'auth-code',
                'state' => 'csrf-state',
            ]);

            $tokenResponse = new OAuth2TokenResponse(
                'Bearer',
                'access-token-123',
                'refresh-token-456',
                3600
            );

            $expectedResponse = new Response(json_encode([
                'status' => 'success',
                'message' => 'OAuth2 authorization completed successfully',
                'data' => [
                    'token_type' => 'Bearer',
                    'expires_in' => 3600,
                    'has_refresh_token' => true,
                ],
            ]), 200, ['Content-Type' => 'application/json']);

            $this->oauth2Manager->shouldReceive('fetchToken')
                ->once()
                ->andReturn($tokenResponse);

            $this->tokenStorage->shouldReceive('store')->once();

            $this->errorHandler->shouldReceive('createSuccessResponse')
                ->once()
                ->andReturn($expectedResponse);

            // Act
            $response = $this->controller->__invoke($request);

            // Assert
            expect($response->headers->get('Content-Type'))->toBe('application/json');
            expect($response->getContent())->toBeJson();

            $content = json_decode($response->getContent(), true);
            expect($content)->toHaveKeys(['status', 'message', 'data']);
            expect($content['data'])->toHaveKeys(['token_type', 'expires_in', 'has_refresh_token']);
        });

        it('returns correct JSON response format for errors', function () {
            // Arrange
            $request = Request::create('/callback', 'GET', [
                'error' => 'access_denied',
            ]);

            $expectedResponse = new Response(json_encode([
                'status' => 'error',
                'message' => 'Authorization was denied by the user: No description provided',
            ]), 400, ['Content-Type' => 'application/json']);

            $this->errorHandler->shouldReceive('handleCallbackError')
                ->once()
                ->with($request)
                ->andReturn($expectedResponse);

            // Act
            $response = $this->controller->__invoke($request);

            // Assert
            expect($response->headers->get('Content-Type'))->toBe('application/json');
            expect($response->getContent())->toBeJson();

            $content = json_decode($response->getContent(), true);
            expect($content)->toHaveKeys(['status', 'message']);
            expect($content)->not->toHaveKey('data');
        });
    });
});
