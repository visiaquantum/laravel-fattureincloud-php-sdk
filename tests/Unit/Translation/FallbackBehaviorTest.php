<?php

use Codeman\FattureInCloud\Exceptions\OAuth2AuthorizationException;
use Codeman\FattureInCloud\Exceptions\OAuth2ConfigurationException;
use Codeman\FattureInCloud\Exceptions\OAuth2TokenExchangeException;
use Codeman\FattureInCloud\Exceptions\OAuth2TokenRefreshException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Lang;

describe('Translation Fallback Behavior', function () {

    describe('Missing Translation Keys', function () {
        test('exception with non-existent error code falls back to default translation', function () {
            App::setLocale('en');

            // Create exception with non-existent error code
            $exception = new OAuth2AuthorizationException(
                'Test exception',
                400,
                null,
                'non_existent_error_code',
                'Test description'
            );

            $message = $exception->getUserFriendlyMessage();
            expect($message)->toBe('An authorization error occurred. Please try again or contact support.');
        });

        test('fallback works correctly for all exception types', function () {
            App::setLocale('en');

            $exceptions = [
                'authorization' => new OAuth2AuthorizationException('Test', 400, null, 'unknown_code', 'Description'),
                'token_exchange' => new OAuth2TokenExchangeException('Test', 400, null, 'unknown_code', 'Description'),
                'token_refresh' => new OAuth2TokenRefreshException('Test', 400, null, 'unknown_code', 'Description'),
                'configuration' => new OAuth2ConfigurationException('Test', 500, null, 'unknown_code', 'Description'),
            ];

            $expectedDefaults = [
                'authorization' => 'An authorization error occurred. Please try again or contact support.',
                'token_exchange' => 'A token exchange error occurred. Please try again or contact support.',
                'token_refresh' => 'A token refresh error occurred. Please log in again.',
                'configuration' => 'A configuration error occurred. Please contact support.',
            ];

            foreach ($exceptions as $type => $exception) {
                $message = $exception->getUserFriendlyMessage();
                expect($message)->toBe($expectedDefaults[$type],
                    "Fallback failed for {$type} exception type"
                );
            }
        });

        test('fallback works in Italian locale as well', function () {
            App::setLocale('it');

            $exceptions = [
                'authorization' => new OAuth2AuthorizationException('Test', 400, null, 'unknown_code', 'Description'),
                'token_exchange' => new OAuth2TokenExchangeException('Test', 400, null, 'unknown_code', 'Description'),
                'token_refresh' => new OAuth2TokenRefreshException('Test', 400, null, 'unknown_code', 'Description'),
                'configuration' => new OAuth2ConfigurationException('Test', 500, null, 'unknown_code', 'Description'),
            ];

            $expectedDefaults = [
                'authorization' => 'Si è verificato un errore di autorizzazione. Riprova o contatta il supporto tecnico.',
                'token_exchange' => 'Si è verificato un errore nello scambio del token. Riprova o contatta il supporto tecnico.',
                'token_refresh' => 'Si è verificato un errore nel rinnovo del token. Effettua nuovamente l\'accesso.',
                'configuration' => 'Si è verificato un errore di configurazione. Contatta il supporto tecnico.',
            ];

            foreach ($exceptions as $type => $exception) {
                $message = $exception->getUserFriendlyMessage();
                expect($message)->toBe($expectedDefaults[$type],
                    "Italian fallback failed for {$type} exception type"
                );
            }
        });

        test('exception with null error code uses default translation', function () {
            App::setLocale('en');

            $exception = new OAuth2AuthorizationException(
                'Test exception',
                400,
                null,
                null, // null error code
                'Test description'
            );

            $message = $exception->getUserFriendlyMessage();
            expect($message)->toBe('An authorization error occurred. Please try again or contact support.');
        });
    });

    describe('Unsupported Locales', function () {
        test('unsupported locale falls back to default locale translation', function () {
            // Set unsupported locale
            App::setLocale('fr');

            $exception = OAuth2AuthorizationException::accessDenied();
            $message = $exception->getUserFriendlyMessage();

            // Laravel falls back to the fallback locale (English) when locale is not supported
            expect($message)->toBe('Authorization was cancelled. Please try again if you want to connect your account.');
        });

        test('invalid locale format does not break translation system', function () {
            // Set completely invalid locale
            App::setLocale('this-is-not-a-valid-locale');

            $exception = OAuth2AuthorizationException::accessDenied();

            expect(function () use ($exception) {
                return $exception->getUserFriendlyMessage();
            })->not->toThrow(\Exception::class);

            $message = $exception->getUserFriendlyMessage();
            expect($message)->toBeString();
            expect($message)->not->toBeEmpty();
        });

        test('empty locale falls back gracefully', function () {
            // Set empty locale
            App::setLocale('');

            $exception = OAuth2AuthorizationException::accessDenied();
            $message = $exception->getUserFriendlyMessage();

            expect($message)->toBeString();
            expect($message)->not->toBeEmpty();
        });
    });

    describe('Translation File Corruption Simulation', function () {
        test('exception handles translation system failures gracefully', function () {
            // This test simulates what happens when the translation system fails
            // We can't easily corrupt translation files, but we can test with missing namespace

            $exception = OAuth2AuthorizationException::accessDenied();

            // Set a non-existent locale
            $originalLocale = App::getLocale();
            App::setLocale('non-existent-locale-xyz');

            $message = $exception->getUserFriendlyMessage();

            // Laravel falls back to the fallback locale (English)
            expect($message)->toBe('Authorization was cancelled. Please try again if you want to connect your account.');

            // Restore original locale
            App::setLocale($originalLocale);
        });
    });

    describe('Edge Cases', function () {
        test('exception with empty error code string uses default', function () {
            App::setLocale('en');

            $exception = new OAuth2AuthorizationException(
                'Test exception',
                400,
                null,
                '', // empty string error code
                'Test description'
            );

            $message = $exception->getUserFriendlyMessage();
            expect($message)->toBe('An authorization error occurred. Please try again or contact support.');
        });

        test('exception with only whitespace error code uses default', function () {
            App::setLocale('en');

            $exception = new OAuth2AuthorizationException(
                'Test exception',
                400,
                null,
                '   ', // whitespace error code
                'Test description'
            );

            $message = $exception->getUserFriendlyMessage();
            expect($message)->toBe('An authorization error occurred. Please try again or contact support.');
        });

        test('translation system respects current locale despite exception creation locale', function () {
            // Create exception in English locale
            App::setLocale('en');
            $exception = OAuth2AuthorizationException::accessDenied();

            // Switch to Italian
            App::setLocale('it');

            // Message should be in Italian, not English
            $message = $exception->getUserFriendlyMessage();
            expect($message)->toBe('L\'autorizzazione è stata annullata. Riprova se desideri connettere il tuo account.');

            // Switch to unsupported locale
            App::setLocale('de');

            // Should fall back to default locale (English)
            $message = $exception->getUserFriendlyMessage();
            expect($message)->toBe('Authorization was cancelled. Please try again if you want to connect your account.');
        });
    });

    describe('Default Translation Completeness', function () {
        test('all OAuth2 exception types have default translations', function () {
            $exceptionTypes = [
                OAuth2AuthorizationException::class => 'authorization',
                OAuth2TokenExchangeException::class => 'token_exchange',
                OAuth2TokenRefreshException::class => 'token_refresh',
                OAuth2ConfigurationException::class => 'configuration',
            ];

            foreach (['en', 'it'] as $locale) {
                App::setLocale($locale);

                foreach ($exceptionTypes as $exceptionClass => $category) {
                    $defaultKey = "fatture-in-cloud::fatture-in-cloud.oauth2.{$category}.default";

                    expect(Lang::has($defaultKey))->toBeTrue(
                        "Missing default translation key '{$defaultKey}' for locale '{$locale}'"
                    );

                    $translation = trans($defaultKey);
                    expect($translation)->not->toBeEmpty(
                        "Empty default translation for '{$defaultKey}' in locale '{$locale}'"
                    );
                    expect($translation)->not->toBe($defaultKey,
                        "Default translation key '{$defaultKey}' not resolved in locale '{$locale}'"
                    );
                }
            }
        });

        test('default translations are appropriate for each exception category', function () {
            App::setLocale('en');

            // Test that default messages are contextually appropriate
            $defaults = [
                'authorization' => trans('fatture-in-cloud::fatture-in-cloud.oauth2.authorization.default'),
                'token_exchange' => trans('fatture-in-cloud::fatture-in-cloud.oauth2.token_exchange.default'),
                'token_refresh' => trans('fatture-in-cloud::fatture-in-cloud.oauth2.token_refresh.default'),
                'configuration' => trans('fatture-in-cloud::fatture-in-cloud.oauth2.configuration.default'),
            ];

            // Authorization default should mention authorization
            expect(strtolower($defaults['authorization']))->toContain('authorization');

            // Token exchange default should mention token or exchange
            expect(
                str_contains(strtolower($defaults['token_exchange']), 'token') ||
                str_contains(strtolower($defaults['token_exchange']), 'exchange')
            )->toBeTrue('Token exchange default should mention token or exchange');

            // Token refresh default should mention token, refresh, or login
            expect(
                str_contains(strtolower($defaults['token_refresh']), 'token') ||
                str_contains(strtolower($defaults['token_refresh']), 'refresh') ||
                str_contains(strtolower($defaults['token_refresh']), 'log in')
            )->toBeTrue('Token refresh default should mention token, refresh, or login');

            // Configuration default should mention configuration
            expect(strtolower($defaults['configuration']))->toContain('configuration');
        });
    });
});
