<?php

use Codeman\FattureInCloud\Exceptions\OAuth2AuthorizationException;
use Codeman\FattureInCloud\Exceptions\OAuth2ConfigurationException;
use Codeman\FattureInCloud\Exceptions\OAuth2ExceptionFactory;
use Codeman\FattureInCloud\Exceptions\OAuth2TokenExchangeException;
use Codeman\FattureInCloud\Exceptions\OAuth2TokenRefreshException;
use Codeman\FattureInCloud\Services\OAuth2ErrorHandler;
use Illuminate\Http\Request;

describe('OAuth2 Error Handling', function () {
    beforeEach(function () {
        $this->errorHandler = new OAuth2ErrorHandler;
    });

    describe('OAuth2Exception Factory Methods', function () {
        test('accessDenied creates proper exception', function () {
            $exception = OAuth2ExceptionFactory::accessDenied('User cancelled');

            expect($exception->getError())->toBe('access_denied');
            expect($exception->getErrorDescription())->toBe('User cancelled');
            expect($exception)->toBeInstanceOf(OAuth2AuthorizationException::class);
            expect($exception->isRetryable())->toBe(false);
            expect($exception->getCode())->toBe(401);
        });

        test('networkFailure creates retryable exception', function () {
            $exception = OAuth2ExceptionFactory::networkFailure('Connection timeout');

            expect($exception->getError())->toBe('network_failure');
            expect($exception)->toBeInstanceOf(OAuth2TokenExchangeException::class);
            expect($exception->isRetryable())->toBe(true);
            expect($exception->getContext()['retry_after'])->toBe(10);
        });

        test('missingConfiguration creates proper exception', function () {
            $exception = OAuth2ExceptionFactory::missingConfiguration('client_id');

            expect($exception->getError())->toBe('missing_configuration');
            expect($exception)->toBeInstanceOf(OAuth2ConfigurationException::class);
            expect($exception->getContext()['missing_config'])->toBe('client_id');
        });

        test('getUserFriendlyMessage returns appropriate messages', function () {
            $accessDenied = OAuth2ExceptionFactory::accessDenied();
            expect($accessDenied->getUserFriendlyMessage())
                ->toBe('Authorization was cancelled. Please try again if you want to connect your account.');

            $networkError = OAuth2ExceptionFactory::networkFailure();
            expect($networkError->getUserFriendlyMessage())
                ->toBe('Network connection failed. Please check your connection and try again.');
        });
    });

    describe('OAuth2ErrorHandler', function () {
        test('handles callback error correctly', function () {
            $request = Request::create('/callback', 'GET', [
                'error' => 'access_denied',
                'error_description' => 'User denied access',
            ]);

            $response = $this->errorHandler->handleCallbackError($request);

            expect($response->getStatusCode())->toBe(401);

            $data = json_decode($response->getContent(), true);
            expect($data['status'])->toBe('error');
            expect($data['error'])->toBe('access_denied');
            expect($data['exception_type'])->toBe(OAuth2AuthorizationException::class);
            expect($data['user_message'])->toContain('Authorization was cancelled');
        });

        test('creates success response correctly', function () {
            $response = $this->errorHandler->createSuccessResponse([
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ], 'Token exchange successful');

            expect($response->getStatusCode())->toBe(200);

            $data = json_decode($response->getContent(), true);
            expect($data['status'])->toBe('success');
            expect($data['message'])->toBe('Token exchange successful');
            expect($data['data']['token_type'])->toBe('Bearer');
        });

        test('includes retry information for retryable errors', function () {
            $exception = OAuth2ExceptionFactory::temporarilyUnavailable();
            $response = $this->errorHandler->createErrorResponse($exception);

            $data = json_decode($response->getContent(), true);
            expect($data['retry']['retryable'])->toBe(true);
            expect($data['retry']['retry_after'])->toBe(60);
        });
    });

    describe('Error Logging Context', function () {
        test('sanitizes sensitive data from logging context', function () {
            $exception = OAuth2AuthorizationException::invalidRequest('Test description', [
                'client_secret' => 'secret123',
                'access_token' => 'token123',
                'safe_data' => 'this_is_safe',
            ]);

            $context = $exception->getLoggingContext();

            expect($context['context']['client_secret'])->toBe('[REDACTED]');
            expect($context['context']['access_token'])->toBe('[REDACTED]');
            expect($context['context']['safe_data'])->toBe('this_is_safe');
        });
    });

    describe('Exception Types', function () {
        test('correctly creates different exception types', function () {
            $authError = OAuth2ExceptionFactory::accessDenied();
            expect($authError)->toBeInstanceOf(OAuth2AuthorizationException::class);

            $tokenError = OAuth2ExceptionFactory::invalidCode();
            expect($tokenError)->toBeInstanceOf(OAuth2TokenExchangeException::class);

            $refreshError = OAuth2ExceptionFactory::invalidRefreshToken();
            expect($refreshError)->toBeInstanceOf(OAuth2TokenRefreshException::class);

            $configError = OAuth2ExceptionFactory::missingConfiguration('client_id');
            expect($configError)->toBeInstanceOf(OAuth2ConfigurationException::class);
        });
    });
});
