<?php

use Codeman\FattureInCloud\Exceptions\OAuth2AuthorizationException;
use Codeman\FattureInCloud\Exceptions\OAuth2ConfigurationException;
use Codeman\FattureInCloud\Exceptions\OAuth2TokenExchangeException;
use Codeman\FattureInCloud\Exceptions\OAuth2TokenRefreshException;
use Illuminate\Support\Facades\App;

describe('Language Switching and Locale Changes', function () {

    describe('Dynamic Locale Switching', function () {
        test('exception messages change when locale is switched', function () {
            // Create an exception instance
            $exception = OAuth2AuthorizationException::accessDenied();

            // Test English
            App::setLocale('en');
            $englishMessage = $exception->getUserFriendlyMessage();
            expect($englishMessage)->toBe('Authorization was cancelled. Please try again if you want to connect your account.');

            // Switch to Italian and test the same exception instance
            App::setLocale('it');
            $italianMessage = $exception->getUserFriendlyMessage();
            expect($italianMessage)->toBe('L\'autorizzazione è stata annullata. Riprova se desideri connettere il tuo account.');

            // Messages should be different
            expect($englishMessage)->not->toBe($italianMessage);

            // Switch back to English
            App::setLocale('en');
            $englishMessageAgain = $exception->getUserFriendlyMessage();
            expect($englishMessageAgain)->toBe($englishMessage);
        });

        test('multiple exception types respond to locale changes', function () {
            $exceptions = [
                'authorization' => OAuth2AuthorizationException::accessDenied(),
                'token_exchange' => OAuth2TokenExchangeException::invalidCode(),
                'token_refresh' => OAuth2TokenRefreshException::invalidRefreshToken(),
                'configuration' => OAuth2ConfigurationException::missingConfiguration('test'),
            ];

            $expectedEnglish = [
                'authorization' => 'Authorization was cancelled. Please try again if you want to connect your account.',
                'token_exchange' => 'The authorization code has expired or is invalid. Please restart the authorization process.',
                'token_refresh' => 'Your session has expired. Please log in again.',
                'configuration' => 'Application configuration is incomplete. Please contact support.',
            ];

            $expectedItalian = [
                'authorization' => 'L\'autorizzazione è stata annullata. Riprova se desideri connettere il tuo account.',
                'token_exchange' => 'Il codice di autorizzazione è scaduto o non valido. Riavvia il processo di autorizzazione.',
                'token_refresh' => 'La tua sessione è scaduta. Effettua nuovamente l\'accesso.',
                'configuration' => 'La configurazione dell\'applicazione è incompleta. Contatta il supporto tecnico.',
            ];

            foreach ($exceptions as $type => $exception) {
                // Test English
                App::setLocale('en');
                expect($exception->getUserFriendlyMessage())->toBe($expectedEnglish[$type]);

                // Test Italian
                App::setLocale('it');
                expect($exception->getUserFriendlyMessage())->toBe($expectedItalian[$type]);
            }
        });

        test('locale changes affect all error types in exception class', function () {
            // Test OAuth2AuthorizationException with multiple error types
            $authExceptions = [
                'access_denied' => OAuth2AuthorizationException::accessDenied(),
                'invalid_request' => OAuth2AuthorizationException::invalidRequest(),
                'unauthorized_client' => OAuth2AuthorizationException::unauthorizedClient(),
                'server_error' => OAuth2AuthorizationException::serverError(),
            ];

            foreach ($authExceptions as $errorType => $exception) {
                App::setLocale('en');
                $englishMessage = $exception->getUserFriendlyMessage();

                App::setLocale('it');
                $italianMessage = $exception->getUserFriendlyMessage();

                expect($englishMessage)->not->toBe($italianMessage,
                    "English and Italian messages should differ for {$errorType}"
                );
                expect($englishMessage)->not->toBeEmpty("English message should not be empty for {$errorType}");
                expect($italianMessage)->not->toBeEmpty("Italian message should not be empty for {$errorType}");
            }
        });
    });

    describe('Concurrent Locale Usage', function () {
        test('multiple exception instances maintain correct translations simultaneously', function () {
            $exception1 = OAuth2AuthorizationException::accessDenied();
            $exception2 = OAuth2TokenExchangeException::invalidCode();

            App::setLocale('en');
            $english1 = $exception1->getUserFriendlyMessage();
            $english2 = $exception2->getUserFriendlyMessage();

            App::setLocale('it');
            $italian1 = $exception1->getUserFriendlyMessage();
            $italian2 = $exception2->getUserFriendlyMessage();

            // Verify each exception maintains its own translation
            expect($english1)->toBe('Authorization was cancelled. Please try again if you want to connect your account.');
            expect($english2)->toBe('The authorization code has expired or is invalid. Please restart the authorization process.');
            expect($italian1)->toBe('L\'autorizzazione è stata annullata. Riprova se desideri connettere il tuo account.');
            expect($italian2)->toBe('Il codice di autorizzazione è scaduto o non valido. Riavvia il processo di autorizzazione.');
        });

        test('creating new exceptions after locale change uses correct translations', function () {
            // Create exception in English
            App::setLocale('en');
            $englishException = OAuth2AuthorizationException::accessDenied();
            $englishMessage = $englishException->getUserFriendlyMessage();

            // Switch to Italian and create new exception
            App::setLocale('it');
            $italianException = OAuth2AuthorizationException::accessDenied();
            $italianMessage = $italianException->getUserFriendlyMessage();

            // Both should respond to current locale, not creation locale
            expect($englishException->getUserFriendlyMessage())->toBe($italianMessage);
            expect($italianException->getUserFriendlyMessage())->toBe($italianMessage);

            // Switch back to English
            App::setLocale('en');
            expect($englishException->getUserFriendlyMessage())->toBe($englishMessage);
            expect($italianException->getUserFriendlyMessage())->toBe($englishMessage);
        });
    });

    describe('Locale Persistence', function () {
        test('locale changes persist across different exception method calls', function () {
            $exception = OAuth2AuthorizationException::accessDenied();

            App::setLocale('it');

            // Call getUserFriendlyMessage multiple times
            $message1 = $exception->getUserFriendlyMessage();
            $message2 = $exception->getUserFriendlyMessage();
            $message3 = $exception->getUserFriendlyMessage();

            // All calls should return Italian translation
            expect($message1)->toBe('L\'autorizzazione è stata annullata. Riprova se desideri connettere il tuo account.');
            expect($message2)->toBe($message1);
            expect($message3)->toBe($message1);
        });

        test('locale state is independent of exception state', function () {
            $exception = OAuth2AuthorizationException::accessDenied();

            // Test that exception properties don't affect locale resolution
            expect($exception->getError())->toBe('access_denied');
            expect($exception->getErrorDescription())->toBe('The user denied the authorization request');
            expect($exception->getCode())->toBe(401);

            App::setLocale('it');
            $italianMessage = $exception->getUserFriendlyMessage();

            // Exception properties should remain unchanged
            expect($exception->getError())->toBe('access_denied');
            expect($exception->getErrorDescription())->toBe('The user denied the authorization request');
            expect($exception->getCode())->toBe(401);

            // But user message should be translated
            expect($italianMessage)->toBe('L\'autorizzazione è stata annullata. Riprova se desideri connettere il tuo account.');
        });
    });

    describe('Unsupported Locale Handling', function () {
        test('unsupported locales fall back to default translation behavior', function () {
            $exception = OAuth2AuthorizationException::accessDenied();

            // Set an unsupported locale
            App::setLocale('fr'); // French is not supported

            $message = $exception->getUserFriendlyMessage();

            // Should fall back to the default locale (English) when locale is not supported
            // Laravel falls back to the fallback locale, not the translation key
            expect($message)->toBe('Authorization was cancelled. Please try again if you want to connect your account.');
        });

        test('invalid locale does not break translation system', function () {
            $exception = OAuth2AuthorizationException::accessDenied();

            // Set an invalid locale
            App::setLocale('invalid-locale-123');

            // Should not throw an exception
            expect(function () use ($exception) {
                return $exception->getUserFriendlyMessage();
            })->not->toThrow(\Exception::class);

            $message = $exception->getUserFriendlyMessage();
            expect($message)->toBeString();
            expect($message)->not->toBeEmpty();
        });
    });

    describe('Runtime Locale Configuration', function () {
        test('locale can be changed multiple times during execution', function () {
            $exception = OAuth2AuthorizationException::accessDenied();

            // Test multiple locale switches
            $locales = ['en', 'it', 'en', 'it', 'en'];
            $expectedMessages = [
                'en' => 'Authorization was cancelled. Please try again if you want to connect your account.',
                'it' => 'L\'autorizzazione è stata annullata. Riprova se desideri connettere il tuo account.',
            ];

            foreach ($locales as $locale) {
                App::setLocale($locale);
                $message = $exception->getUserFriendlyMessage();
                expect($message)->toBe($expectedMessages[$locale]);
            }
        });
    });
});
