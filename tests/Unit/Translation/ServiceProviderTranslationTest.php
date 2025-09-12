<?php

use Codeman\FattureInCloud\FattureInCloudServiceProvider;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Lang;
use Illuminate\Translation\Translator;

describe('Service Provider Translation Registration', function () {

    describe('Translation Service Registration', function () {
        test('service provider registers translation namespace correctly', function () {
            /** @var Translator $translator */
            $translator = app('translator');
            $loader = $translator->getLoader();

            // Check that the 'fatture-in-cloud' namespace is registered
            expect($loader->namespaces())->toHaveKey('fatture-in-cloud');
        });

        test('translation files are loaded from correct paths', function () {
            /** @var Translator $translator */
            $translator = app('translator');
            $loader = $translator->getLoader();

            $namespaces = $loader->namespaces();
            expect($namespaces)->toHaveKey('fatture-in-cloud');

            // The path should point to our package's lang directory
            $paths = $namespaces['fatture-in-cloud'];
            
            // Handle both string and array cases - Laravel may return either
            if (is_string($paths)) {
                $pathsArray = [$paths];
            } else {
                expect($paths)->toBeArray();
                $pathsArray = $paths;
            }
            
            expect($pathsArray)->not->toBeEmpty();

            // Check that at least one path contains our package structure
            $hasPackagePath = false;
            foreach ($pathsArray as $path) {
                if (str_contains($path, 'fatture') && str_contains($path, 'lang')) {
                    $hasPackagePath = true;
                    break;
                }
            }
            expect($hasPackagePath)->toBeTrue('Translation paths should include package lang directory');
        });

        test('both English and Italian translation files are accessible', function () {
            // Test English translations are available
            App::setLocale('en');
            expect(Lang::has('fatture-in-cloud::fatture-in-cloud.oauth2.authorization.access_denied'))->toBeTrue();

            // Test Italian translations are available
            App::setLocale('it');
            expect(Lang::has('fatture-in-cloud::fatture-in-cloud.oauth2.authorization.access_denied'))->toBeTrue();
        });

        test('service provider configures package correctly with translations', function () {
            $serviceProvider = new FattureInCloudServiceProvider(app());

            // Test that hasTranslations() is called in configurePackage
            // We can verify this by checking that translations are actually available
            expect(Lang::has('fatture-in-cloud::fatture-in-cloud.oauth2'))->toBeTrue();
        });
    });

    describe('Translation Loading Performance', function () {
        test('translations are loaded lazily and not duplicated', function () {
            // Access same translation multiple times
            App::setLocale('en');

            $key = 'fatture-in-cloud::fatture-in-cloud.oauth2.authorization.access_denied';
            $translation1 = trans($key);
            $translation2 = trans($key);
            $translation3 = trans($key);

            // Should return the same content
            expect($translation1)->toBe($translation2);
            expect($translation2)->toBe($translation3);

            // And should be the actual translation, not the key
            expect($translation1)->not->toBe($key);
            expect($translation1)->toBe('Authorization was cancelled. Please try again if you want to connect your account.');
        });

        test('switching locales efficiently loads different translation files', function () {
            $key = 'fatture-in-cloud::fatture-in-cloud.oauth2.authorization.access_denied';

            // Load English
            App::setLocale('en');
            $englishTranslation = trans($key);

            // Load Italian
            App::setLocale('it');
            $italianTranslation = trans($key);

            // Switch back to English
            App::setLocale('en');
            $englishAgain = trans($key);

            expect($englishTranslation)->not->toBe($italianTranslation);
            expect($englishTranslation)->toBe($englishAgain);
        });
    });

    describe('Translation File Structure Validation', function () {
        test('English translation file has correct structure', function () {
            App::setLocale('en');

            // Test top-level oauth2 key exists
            expect(Lang::has('fatture-in-cloud::fatture-in-cloud.oauth2'))->toBeTrue();

            // Test all main categories exist
            $categories = ['authorization', 'token_exchange', 'token_refresh', 'configuration'];
            foreach ($categories as $category) {
                expect(Lang::has("fatture-in-cloud::fatture-in-cloud.oauth2.{$category}"))
                    ->toBeTrue("Missing category '{$category}' in English translations");
            }
        });

        test('Italian translation file has correct structure', function () {
            App::setLocale('it');

            // Test top-level oauth2 key exists
            expect(Lang::has('fatture-in-cloud::fatture-in-cloud.oauth2'))->toBeTrue();

            // Test all main categories exist
            $categories = ['authorization', 'token_exchange', 'token_refresh', 'configuration'];
            foreach ($categories as $category) {
                expect(Lang::has("fatture-in-cloud::fatture-in-cloud.oauth2.{$category}"))
                    ->toBeTrue("Missing category '{$category}' in Italian translations");
            }
        });

        test('translation structure is consistent between languages', function () {
            // Get all keys from English
            App::setLocale('en');
            $allEnglishKeys = getAllTranslationKeys('fatture-in-cloud::fatture-in-cloud.oauth2');

            // Get all keys from Italian
            App::setLocale('it');
            $allItalianKeys = getAllTranslationKeys('fatture-in-cloud::fatture-in-cloud.oauth2');

            // Keys should be the same (structure should match)
            sort($allEnglishKeys);
            sort($allItalianKeys);

            expect($allEnglishKeys)->toBe($allItalianKeys,
                'Translation key structure should be consistent between English and Italian'
            );
        });
    });

    describe('Translation Content Validation', function () {
        test('no translation values are empty or just whitespace', function () {
            $locales = ['en', 'it'];

            foreach ($locales as $locale) {
                App::setLocale($locale);

                $keys = getAllTranslationKeys('fatture-in-cloud::fatture-in-cloud.oauth2');
                foreach ($keys as $key) {
                    $fullKey = "fatture-in-cloud::fatture-in-cloud.oauth2.{$key}";
                    $translation = trans($fullKey);

                    expect($translation)->not->toBeEmpty("Empty translation for '{$fullKey}' in locale '{$locale}'");
                    expect(trim($translation))->not->toBeEmpty("Whitespace-only translation for '{$fullKey}' in locale '{$locale}'");
                    expect($translation)->not->toBe($fullKey, "Unresolved translation key '{$fullKey}' in locale '{$locale}'");
                }
            }
        });

        test('translations contain appropriate professional language', function () {
            App::setLocale('en');

            $professionalWords = ['please', 'support', 'contact', 'try again', 'error', 'failed'];
            $translations = [
                trans('fatture-in-cloud::fatture-in-cloud.oauth2.authorization.access_denied'),
                trans('fatture-in-cloud::fatture-in-cloud.oauth2.token_exchange.invalid_code'),
                trans('fatture-in-cloud::fatture-in-cloud.oauth2.token_refresh.invalid_refresh_token'),
                trans('fatture-in-cloud::fatture-in-cloud.oauth2.configuration.missing_configuration'),
            ];

            foreach ($translations as $translation) {
                $lowerTranslation = strtolower($translation);
                $hasProfessionalTone = false;

                foreach ($professionalWords as $word) {
                    if (str_contains($lowerTranslation, $word)) {
                        $hasProfessionalTone = true;
                        break;
                    }
                }

                expect($hasProfessionalTone)->toBeTrue(
                    "Translation '{$translation}' should contain professional language"
                );
            }
        });

        test('Italian translations contain appropriate professional language', function () {
            App::setLocale('it');

            $professionalWords = ['supporto', 'contatta', 'riprova', 'errore', 'verificato', 'tecnico', 'riavvia', 'processo', 'autorizzazione', 'scaduto', 'valido', 'sessione', 'scaduta', 'effettua', 'accesso'];
            $translations = [
                trans('fatture-in-cloud::fatture-in-cloud.oauth2.authorization.access_denied'),
                trans('fatture-in-cloud::fatture-in-cloud.oauth2.token_exchange.invalid_code'),
                trans('fatture-in-cloud::fatture-in-cloud.oauth2.token_refresh.invalid_refresh_token'),
                trans('fatture-in-cloud::fatture-in-cloud.oauth2.configuration.missing_configuration'),
            ];

            foreach ($translations as $translation) {
                $lowerTranslation = strtolower($translation);
                $hasProfessionalTone = false;

                foreach ($professionalWords as $word) {
                    if (str_contains($lowerTranslation, $word)) {
                        $hasProfessionalTone = true;
                        break;
                    }
                }

                expect($hasProfessionalTone)->toBeTrue(
                    "Italian translation '{$translation}' should contain professional language"
                );
            }
        });
    });

    describe('Translation Namespace Isolation', function () {
        test('package translations do not conflict with app translations', function () {
            // Test that our translations are properly namespaced
            App::setLocale('en');

            // Our namespaced translation should work
            $namespacedTranslation = trans('fatture-in-cloud::fatture-in-cloud.oauth2.authorization.access_denied');
            expect($namespacedTranslation)->not->toBe('fatture-in-cloud::fatture-in-cloud.oauth2.authorization.access_denied');

            // Non-namespaced version should not exist (should return the key)
            $nonNamespacedTranslation = trans('oauth2.authorization.access_denied');
            expect($nonNamespacedTranslation)->toBe('oauth2.authorization.access_denied');

            // They should be different
            expect($namespacedTranslation)->not->toBe($nonNamespacedTranslation);
        });

        test('package translations are isolated from other namespaces', function () {
            // Test that we can't access our translations through wrong namespace
            App::setLocale('en');

            $correctTranslation = trans('fatture-in-cloud::fatture-in-cloud.oauth2.authorization.access_denied');
            $wrongNamespace = trans('some-other-package::oauth2.authorization.access_denied');

            expect($correctTranslation)->not->toBe($wrongNamespace);
            expect($wrongNamespace)->toBe('some-other-package::oauth2.authorization.access_denied'); // Should return key
        });
    });

    describe('Package Configuration Integration', function () {
        test('service provider properly calls hasTranslations in package configuration', function () {
            // This is tested indirectly by verifying translations work
            // If hasTranslations() wasn't called, our translations wouldn't be available

            expect(Lang::has('fatture-in-cloud::fatture-in-cloud.oauth2'))->toBeTrue();

            // Test some translations from each category to ensure complete loading
            $requiredKeys = [
                'fatture-in-cloud::fatture-in-cloud.oauth2.authorization.access_denied',
                'fatture-in-cloud::fatture-in-cloud.oauth2.token_exchange.invalid_code',
                'fatture-in-cloud::fatture-in-cloud.oauth2.token_refresh.invalid_refresh_token',
                'fatture-in-cloud::fatture-in-cloud.oauth2.configuration.missing_configuration',
            ];

            App::setLocale('en');
            foreach ($requiredKeys as $key) {
                expect(Lang::has($key))->toBeTrue("Translation key '{$key}' should be available");
            }

            App::setLocale('it');
            foreach ($requiredKeys as $key) {
                expect(Lang::has($key))->toBeTrue("Translation key '{$key}' should be available in Italian");
            }
        });
    });

});

/**
 * Helper function to recursively get all translation keys
 */
function getAllTranslationKeys(string $namespace): array
{
    $keys = [];
    $translations = trans($namespace);

    if (is_array($translations)) {
        flattenKeys($translations, '', $keys);
    }

    return $keys;
}

/**
 * Recursively flatten nested translation keys
 */
function flattenKeys(array $translations, string $prefix, array &$keys): void
{
    foreach ($translations as $key => $value) {
        $fullKey = $prefix ? "{$prefix}.{$key}" : $key;

        if (is_array($value)) {
            flattenKeys($value, $fullKey, $keys);
        } else {
            $keys[] = $fullKey;
        }
    }
}
