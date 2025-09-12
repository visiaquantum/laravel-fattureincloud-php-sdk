<?php

use Codeman\FattureInCloud\Exceptions\OAuth2AuthorizationException;
use Codeman\FattureInCloud\Exceptions\OAuth2ConfigurationException;
use Codeman\FattureInCloud\Exceptions\OAuth2ExceptionFactory;
use Codeman\FattureInCloud\Exceptions\OAuth2TokenExchangeException;
use Codeman\FattureInCloud\Exceptions\OAuth2TokenRefreshException;
use Codeman\FattureInCloud\Services\OAuth2ErrorHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

describe('Translation System Integration', function () {
    beforeEach(function () {
        $this->errorHandler = new OAuth2ErrorHandler;
    });

    describe('End-to-End Translation Workflow', function () {
        test('complete OAuth2 error flow produces translated messages', function () {
            // Test the complete flow from OAuth2 error to translated user message

            // 1. Simulate OAuth2 callback error
            $request = Request::create('/callback', 'GET', [
                'error' => 'access_denied',
                'error_description' => 'User denied access',
                'state' => 'test-state',
            ]);

            // Test English flow
            App::setLocale('en');
            $response = $this->errorHandler->handleCallbackError($request);
            $data = json_decode($response->getContent(), true);

            expect($data['user_message'])->toBe('Authorization was cancelled. Please try again if you want to connect your account.');

            // Test Italian flow
            App::setLocale('it');
            $response = $this->errorHandler->handleCallbackError($request);
            $data = json_decode($response->getContent(), true);

            expect($data['user_message'])->toBe('L\'autorizzazione è stata annullata. Riprova se desideri connettere il tuo account.');
        });

        test('exception factory creates properly translated exceptions', function () {
            $testCases = [
                ['method' => 'accessDenied', 'args' => [], 'category' => 'authorization'],
                ['method' => 'invalidCode', 'args' => [], 'category' => 'token_exchange'],
                ['method' => 'invalidRefreshToken', 'args' => [], 'category' => 'token_refresh'],
                ['method' => 'missingConfiguration', 'args' => ['client_id'], 'category' => 'configuration'],
            ];

            foreach ($testCases as $testCase) {
                // Create exception using factory
                $exception = OAuth2ExceptionFactory::{$testCase['method']}(...$testCase['args']);

                // Test English
                App::setLocale('en');
                $englishMessage = $exception->getUserFriendlyMessage();
                expect($englishMessage)->not->toBeEmpty();
                expect($englishMessage)->not->toContain('fatture-in-cloud::');

                // Test Italian
                App::setLocale('it');
                $italianMessage = $exception->getUserFriendlyMessage();
                expect($italianMessage)->not->toBeEmpty();
                expect($italianMessage)->not->toContain('fatture-in-cloud::');
                expect($italianMessage)->not->toBe($englishMessage);
            }
        });
    });

    describe('Exception Type Translation Integration', function () {
        test('all exception types integrate correctly with translation system', function () {
            $exceptionFactories = [
                'OAuth2AuthorizationException' => fn () => OAuth2AuthorizationException::accessDenied(),
                'OAuth2TokenExchangeException' => fn () => OAuth2TokenExchangeException::invalidCode(),
                'OAuth2TokenRefreshException' => fn () => OAuth2TokenRefreshException::invalidRefreshToken(),
                'OAuth2ConfigurationException' => fn () => OAuth2ConfigurationException::missingConfiguration('test'),
            ];

            foreach ($exceptionFactories as $exceptionType => $factory) {
                $exception = $factory();

                // Test that exception implements translation correctly
                expect(method_exists($exception, 'getUserFriendlyMessage'))
                    ->toBeTrue("{$exceptionType} should implement getUserFriendlyMessage");

                // Test translations work in both languages
                App::setLocale('en');
                $englishMessage = $exception->getUserFriendlyMessage();

                App::setLocale('it');
                $italianMessage = $exception->getUserFriendlyMessage();

                expect($englishMessage)->not->toBeEmpty("{$exceptionType} English message should not be empty");
                expect($italianMessage)->not->toBeEmpty("{$exceptionType} Italian message should not be empty");
                expect($englishMessage)->not->toBe($italianMessage, "{$exceptionType} messages should differ by language");
            }
        });

        test('exception inheritance maintains translation functionality', function () {
            // Test that all OAuth2 exception subclasses properly inherit and implement translation
            $exceptions = [
                OAuth2AuthorizationException::accessDenied(),
                OAuth2TokenExchangeException::invalidCode(),
                OAuth2TokenRefreshException::invalidRefreshToken(),
                OAuth2ConfigurationException::missingConfiguration('test'),
            ];

            foreach ($exceptions as $exception) {
                // Each should be instance of base OAuth2Exception
                expect($exception)->toBeInstanceOf(\Codeman\FattureInCloud\Exceptions\OAuth2Exception::class);

                // Each should implement getUserFriendlyMessage (abstract method from base)
                expect(method_exists($exception, 'getUserFriendlyMessage'))->toBeTrue();

                // Method should return translated strings
                App::setLocale('en');
                $message = $exception->getUserFriendlyMessage();
                expect($message)->toBeString();
                expect($message)->not->toBeEmpty();
            }
        });
    });

    describe('Real-World Scenario Testing', function () {
        test('OAuth2 authorization flow with different error conditions', function () {
            $errorScenarios = [
                ['error' => 'access_denied', 'description' => 'User cancelled'],
                ['error' => 'invalid_request', 'description' => 'Missing parameter'],
                ['error' => 'server_error', 'description' => 'Internal server error'],
                ['error' => 'temporarily_unavailable', 'description' => 'Service maintenance'],
            ];

            foreach ($errorScenarios as $scenario) {
                $request = Request::create('/callback', 'GET', [
                    'error' => $scenario['error'],
                    'error_description' => $scenario['description'],
                ]);

                // Test English
                App::setLocale('en');
                $response = $this->errorHandler->handleCallbackError($request);
                $data = json_decode($response->getContent(), true);

                expect($data)->toHaveKey('user_message');
                expect($data['user_message'])->not->toBeEmpty();
                expect($data['user_message'])->not->toContain('fatture-in-cloud::');

                // Test Italian
                App::setLocale('it');
                $response = $this->errorHandler->handleCallbackError($request);
                $data = json_decode($response->getContent(), true);

                expect($data)->toHaveKey('user_message');
                expect($data['user_message'])->not->toBeEmpty();
                expect($data['user_message'])->not->toContain('fatture-in-cloud::');
            }
        });

        test('token exchange errors provide appropriate translated messages', function () {
            $tokenExchangeErrors = [
                OAuth2TokenExchangeException::invalidCode(),
                OAuth2TokenExchangeException::invalidClientCredentials(),
                OAuth2TokenExchangeException::networkFailure(),
            ];

            foreach ($tokenExchangeErrors as $exception) {
                // Test that error responses include translated messages
                $response = $this->errorHandler->createErrorResponse($exception);
                $data = json_decode($response->getContent(), true);

                expect($data)->toHaveKey('user_message');
                expect($data['user_message'])->not->toBeEmpty();
                expect($data['user_message'])->not->toContain('fatture-in-cloud::');
            }
        });

        test('configuration errors provide developer and user appropriate messages', function () {
            $configErrors = [
                OAuth2ConfigurationException::missingConfiguration('client_id'),
                OAuth2ConfigurationException::invalidRedirectUrl('invalid-url'),
                OAuth2ConfigurationException::malformedConfiguration(),
            ];

            foreach ($configErrors as $exception) {
                // Test English
                App::setLocale('en');
                $userMessage = $exception->getUserFriendlyMessage();
                expect($userMessage)->toContain('support');
                expect($userMessage)->not->toContain('fatture-in-cloud::');

                // Test Italian
                App::setLocale('it');
                $userMessage = $exception->getUserFriendlyMessage();
                expect($userMessage)->toContain('supporto');
                expect($userMessage)->not->toContain('fatture-in-cloud::');
            }
        });
    });

    describe('Translation Performance Integration', function () {
        test('translation system performs efficiently under load', function () {
            $exception = OAuth2AuthorizationException::accessDenied();

            // Measure performance of multiple translation calls
            $iterations = 100;
            $startTime = microtime(true);

            for ($i = 0; $i < $iterations; $i++) {
                App::setLocale($i % 2 === 0 ? 'en' : 'it');
                $message = $exception->getUserFriendlyMessage();
                expect($message)->not->toBeEmpty();
            }

            $endTime = microtime(true);
            $totalTime = $endTime - $startTime;

            // Should complete in reasonable time (less than 1 second for 100 iterations)
            expect($totalTime)->toBeLessThan(1.0, 'Translation system should be performant');
        });

        test('multiple exception types can be translated simultaneously', function () {
            $exceptions = [
                OAuth2AuthorizationException::accessDenied(),
                OAuth2TokenExchangeException::invalidCode(),
                OAuth2TokenRefreshException::invalidRefreshToken(),
                OAuth2ConfigurationException::missingConfiguration('test'),
            ];

            // Test that all exceptions can be translated in the same request
            App::setLocale('en');
            $englishMessages = array_map(fn ($e) => $e->getUserFriendlyMessage(), $exceptions);

            App::setLocale('it');
            $italianMessages = array_map(fn ($e) => $e->getUserFriendlyMessage(), $exceptions);

            // All messages should be valid and different between languages
            for ($i = 0; $i < count($exceptions); $i++) {
                expect($englishMessages[$i])->not->toBeEmpty();
                expect($italianMessages[$i])->not->toBeEmpty();
                expect($englishMessages[$i])->not->toBe($italianMessages[$i]);
            }
        });
    });

    describe('Translation System Edge Cases Integration', function () {
        test('system handles mixed error types gracefully', function () {
            // Create a mixed bag of exception types
            $exceptions = [
                OAuth2AuthorizationException::accessDenied(),
                OAuth2TokenExchangeException::invalidCode(),
                new OAuth2TokenRefreshException('Custom message', 400, null, 'unknown_error', 'Custom description'),
                OAuth2ConfigurationException::missingConfiguration('unknown_config'),
            ];

            foreach (['en', 'it'] as $locale) {
                App::setLocale($locale);

                foreach ($exceptions as $exception) {
                    $message = $exception->getUserFriendlyMessage();
                    expect($message)->toBeString();
                    expect($message)->not->toBeEmpty();
                    // Should not contain unresolved translation keys
                    expect($message)->not->toContain('fatture-in-cloud::');
                }
            }
        });

        test('system recovers from translation failures', function () {
            $exception = OAuth2AuthorizationException::accessDenied();

            // Test with valid locale
            App::setLocale('en');
            $validMessage = $exception->getUserFriendlyMessage();
            expect($validMessage)->not->toContain('fatture-in-cloud::');

            // Test with invalid locale (should fall back to key)
            App::setLocale('invalid-locale');
            $fallbackMessage = $exception->getUserFriendlyMessage();
            expect($fallbackMessage)->toBe('fatture-in-cloud::fatture-in-cloud.oauth2.authorization.access_denied');

            // Test recovery by switching back to valid locale
            App::setLocale('en');
            $recoveredMessage = $exception->getUserFriendlyMessage();
            expect($recoveredMessage)->toBe($validMessage);
        });
    });

    describe('Translation System Documentation Compliance', function () {
        test('all documented translation keys are actually available', function () {
            // This test ensures that all keys referenced in code documentation exist
            $documentedKeys = [
                'fatture-in-cloud::fatture-in-cloud.oauth2.authorization.access_denied',
                'fatture-in-cloud::fatture-in-cloud.oauth2.authorization.invalid_request',
                'fatture-in-cloud::fatture-in-cloud.oauth2.authorization.unauthorized_client',
                'fatture-in-cloud::fatture-in-cloud.oauth2.authorization.unsupported_response_type',
                'fatture-in-cloud::fatture-in-cloud.oauth2.authorization.invalid_scope',
                'fatture-in-cloud::fatture-in-cloud.oauth2.authorization.server_error',
                'fatture-in-cloud::fatture-in-cloud.oauth2.authorization.temporarily_unavailable',
                'fatture-in-cloud::fatture-in-cloud.oauth2.authorization.default',

                'fatture-in-cloud::fatture-in-cloud.oauth2.token_exchange.invalid_code',
                'fatture-in-cloud::fatture-in-cloud.oauth2.token_exchange.invalid_client_credentials',
                'fatture-in-cloud::fatture-in-cloud.oauth2.token_exchange.network_failure',
                'fatture-in-cloud::fatture-in-cloud.oauth2.token_exchange.default',

                'fatture-in-cloud::fatture-in-cloud.oauth2.token_refresh.invalid_refresh_token',
                'fatture-in-cloud::fatture-in-cloud.oauth2.token_refresh.client_authentication_failed',
                'fatture-in-cloud::fatture-in-cloud.oauth2.token_refresh.token_revoked',
                'fatture-in-cloud::fatture-in-cloud.oauth2.token_refresh.default',

                'fatture-in-cloud::fatture-in-cloud.oauth2.configuration.missing_configuration',
                'fatture-in-cloud::fatture-in-cloud.oauth2.configuration.invalid_redirect_url',
                'fatture-in-cloud::fatture-in-cloud.oauth2.configuration.malformed_configuration',
                'fatture-in-cloud::fatture-in-cloud.oauth2.configuration.default',
            ];

            foreach (['en', 'it'] as $locale) {
                App::setLocale($locale);

                foreach ($documentedKeys as $key) {
                    expect(trans($key))->not->toBe($key,
                        "Translation key '{$key}' should be available in locale '{$locale}'"
                    );
                }
            }
        });

        test('translation system supports the documented workflow', function () {
            // Test the workflow: Exception creation -> Translation -> User display

            // 1. Create exception
            $exception = OAuth2AuthorizationException::accessDenied('User clicked cancel');

            // 2. Get user-friendly message (this uses translation system)
            App::setLocale('en');
            $userMessage = $exception->getUserFriendlyMessage();

            // 3. Verify message is suitable for user display
            expect($userMessage)->toBe('Authorization was cancelled. Please try again if you want to connect your account.');
            expect($userMessage)->not->toContain('fatture-in-cloud::');
            expect($userMessage)->not->toBeEmpty();

            // 4. Verify logging context still has technical details
            $loggingContext = $exception->getLoggingContext();
            expect($loggingContext)->toHaveKey('oauth2_error');
            expect($loggingContext)->toHaveKey('error_description');
            expect($loggingContext['oauth2_error'])->toBe('access_denied');
        });
    });
});
