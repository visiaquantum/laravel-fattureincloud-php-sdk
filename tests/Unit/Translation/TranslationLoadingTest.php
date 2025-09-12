<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Lang;

describe('Translation File Loading', function () {

    describe('Translation Files Accessibility', function () {
        test('English translation file is properly loaded and accessible', function () {
            App::setLocale('en');

            expect(Lang::has('fatture-in-cloud::fatture-in-cloud.oauth2.authorization.access_denied'))->toBeTrue();
            expect(Lang::has('fatture-in-cloud::fatture-in-cloud.oauth2.token_exchange.invalid_code'))->toBeTrue();
            expect(Lang::has('fatture-in-cloud::fatture-in-cloud.oauth2.token_refresh.invalid_refresh_token'))->toBeTrue();
            expect(Lang::has('fatture-in-cloud::fatture-in-cloud.oauth2.configuration.missing_configuration'))->toBeTrue();
        });

        test('Italian translation file is properly loaded and accessible', function () {
            App::setLocale('it');

            expect(Lang::has('fatture-in-cloud::fatture-in-cloud.oauth2.authorization.access_denied'))->toBeTrue();
            expect(Lang::has('fatture-in-cloud::fatture-in-cloud.oauth2.token_exchange.invalid_code'))->toBeTrue();
            expect(Lang::has('fatture-in-cloud::fatture-in-cloud.oauth2.token_refresh.invalid_refresh_token'))->toBeTrue();
            expect(Lang::has('fatture-in-cloud::fatture-in-cloud.oauth2.configuration.missing_configuration'))->toBeTrue();
        });

        test('all OAuth2 authorization error keys exist in both languages', function () {
            $authorizationKeys = [
                'access_denied',
                'invalid_request',
                'unauthorized_client',
                'unsupported_response_type',
                'invalid_scope',
                'server_error',
                'temporarily_unavailable',
                'default',
            ];

            foreach (['en', 'it'] as $locale) {
                App::setLocale($locale);

                foreach ($authorizationKeys as $key) {
                    $translationKey = "fatture-in-cloud::fatture-in-cloud.oauth2.authorization.{$key}";
                    expect(Lang::has($translationKey))
                        ->toBeTrue("Missing translation key '{$translationKey}' for locale '{$locale}'");
                }
            }
        });

        test('all OAuth2 token exchange error keys exist in both languages', function () {
            $tokenExchangeKeys = [
                'invalid_code',
                'invalid_client_credentials',
                'network_failure',
                'default',
            ];

            foreach (['en', 'it'] as $locale) {
                App::setLocale($locale);

                foreach ($tokenExchangeKeys as $key) {
                    $translationKey = "fatture-in-cloud::fatture-in-cloud.oauth2.token_exchange.{$key}";
                    expect(Lang::has($translationKey))
                        ->toBeTrue("Missing translation key '{$translationKey}' for locale '{$locale}'");
                }
            }
        });

        test('all OAuth2 token refresh error keys exist in both languages', function () {
            $tokenRefreshKeys = [
                'invalid_refresh_token',
                'client_authentication_failed',
                'token_revoked',
                'default',
            ];

            foreach (['en', 'it'] as $locale) {
                App::setLocale($locale);

                foreach ($tokenRefreshKeys as $key) {
                    $translationKey = "fatture-in-cloud::fatture-in-cloud.oauth2.token_refresh.{$key}";
                    expect(Lang::has($translationKey))
                        ->toBeTrue("Missing translation key '{$translationKey}' for locale '{$locale}'");
                }
            }
        });

        test('all OAuth2 configuration error keys exist in both languages', function () {
            $configurationKeys = [
                'missing_configuration',
                'invalid_redirect_url',
                'malformed_configuration',
                'default',
            ];

            foreach (['en', 'it'] as $locale) {
                App::setLocale($locale);

                foreach ($configurationKeys as $key) {
                    $translationKey = "fatture-in-cloud::fatture-in-cloud.oauth2.configuration.{$key}";
                    expect(Lang::has($translationKey))
                        ->toBeTrue("Missing translation key '{$translationKey}' for locale '{$locale}'");
                }
            }
        });
    });

    describe('Translation Content Validation', function () {
        test('English translations return non-empty strings', function () {
            App::setLocale('en');

            $translationKeys = [
                'fatture-in-cloud::fatture-in-cloud.oauth2.authorization.access_denied',
                'fatture-in-cloud::fatture-in-cloud.oauth2.token_exchange.invalid_code',
                'fatture-in-cloud::fatture-in-cloud.oauth2.token_refresh.invalid_refresh_token',
                'fatture-in-cloud::fatture-in-cloud.oauth2.configuration.missing_configuration',
            ];

            foreach ($translationKeys as $key) {
                $translation = trans($key);
                expect($translation)->not->toBeEmpty("Translation for '{$key}' is empty");
                expect($translation)->not->toBe($key, "Translation for '{$key}' falls back to key");
            }
        });

        test('Italian translations return non-empty strings', function () {
            App::setLocale('it');

            $translationKeys = [
                'fatture-in-cloud::fatture-in-cloud.oauth2.authorization.access_denied',
                'fatture-in-cloud::fatture-in-cloud.oauth2.token_exchange.invalid_code',
                'fatture-in-cloud::fatture-in-cloud.oauth2.token_refresh.invalid_refresh_token',
                'fatture-in-cloud::fatture-in-cloud.oauth2.configuration.missing_configuration',
            ];

            foreach ($translationKeys as $key) {
                $translation = trans($key);
                expect($translation)->not->toBeEmpty("Translation for '{$key}' is empty");
                expect($translation)->not->toBe($key, "Translation for '{$key}' falls back to key");
            }
        });

        test('translations differ between English and Italian', function () {
            $translationKeys = [
                'fatture-in-cloud::fatture-in-cloud.oauth2.authorization.access_denied',
                'fatture-in-cloud::fatture-in-cloud.oauth2.token_exchange.invalid_code',
                'fatture-in-cloud::fatture-in-cloud.oauth2.token_refresh.invalid_refresh_token',
                'fatture-in-cloud::fatture-in-cloud.oauth2.configuration.missing_configuration',
            ];

            foreach ($translationKeys as $key) {
                App::setLocale('en');
                $englishTranslation = trans($key);

                App::setLocale('it');
                $italianTranslation = trans($key);

                expect($englishTranslation)->not->toBe($italianTranslation,
                    "English and Italian translations are identical for '{$key}'"
                );
            }
        });
    });

    describe('Namespace Resolution', function () {
        test('translations can be accessed with and without namespace', function () {
            App::setLocale('en');

            // With namespace
            $withNamespace = trans('fatture-in-cloud::fatture-in-cloud.oauth2.authorization.access_denied');
            expect($withNamespace)->not->toBeEmpty();

            // Check that namespace is actually required (without namespace should fail)
            $withoutNamespace = trans('oauth2.authorization.access_denied');
            expect($withoutNamespace)->toBe('oauth2.authorization.access_denied'); // Falls back to key
        });

        test('translation namespace is correctly registered', function () {
            $loader = app('translator')->getLoader();

            // Check that the namespace exists
            expect($loader->namespaces())->toHaveKey('fatture-in-cloud');
        });
    });
});
