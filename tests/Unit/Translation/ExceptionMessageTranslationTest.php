<?php

use Codeman\FattureInCloud\Exceptions\OAuth2AuthorizationException;
use Codeman\FattureInCloud\Exceptions\OAuth2ConfigurationException;
use Codeman\FattureInCloud\Exceptions\OAuth2TokenExchangeException;
use Codeman\FattureInCloud\Exceptions\OAuth2TokenRefreshException;
use Illuminate\Support\Facades\App;

describe('Exception Message Translation', function () {

    describe('OAuth2AuthorizationException Translation', function () {
        test('getUserFriendlyMessage returns English translations', function () {
            App::setLocale('en');

            $accessDenied = OAuth2AuthorizationException::accessDenied();
            expect($accessDenied->getUserFriendlyMessage())
                ->toBe('Authorization was cancelled. Please try again if you want to connect your account.');

            $invalidRequest = OAuth2AuthorizationException::invalidRequest();
            expect($invalidRequest->getUserFriendlyMessage())
                ->toBe('There was a problem with the authorization request. Please contact support.');

            $unauthorizedClient = OAuth2AuthorizationException::unauthorizedClient();
            expect($unauthorizedClient->getUserFriendlyMessage())
                ->toBe('This application is not authorized to access your account.');

            $unsupportedResponseType = OAuth2AuthorizationException::unsupportedResponseType();
            expect($unsupportedResponseType->getUserFriendlyMessage())
                ->toBe('Authorization method not supported. Please contact support.');

            $invalidScope = OAuth2AuthorizationException::invalidScope();
            expect($invalidScope->getUserFriendlyMessage())
                ->toBe('The requested permissions are not available.');

            $serverError = OAuth2AuthorizationException::serverError();
            expect($serverError->getUserFriendlyMessage())
                ->toBe('A temporary server error occurred. Please try again in a moment.');

            $temporarilyUnavailable = OAuth2AuthorizationException::temporarilyUnavailable();
            expect($temporarilyUnavailable->getUserFriendlyMessage())
                ->toBe('The service is temporarily unavailable. Please try again later.');
        });

        test('getUserFriendlyMessage returns Italian translations', function () {
            App::setLocale('it');

            $accessDenied = OAuth2AuthorizationException::accessDenied();
            expect($accessDenied->getUserFriendlyMessage())
                ->toBe('L\'autorizzazione è stata annullata. Riprova se desideri connettere il tuo account.');

            $invalidRequest = OAuth2AuthorizationException::invalidRequest();
            expect($invalidRequest->getUserFriendlyMessage())
                ->toBe('Si è verificato un problema con la richiesta di autorizzazione. Contatta il supporto tecnico.');

            $unauthorizedClient = OAuth2AuthorizationException::unauthorizedClient();
            expect($unauthorizedClient->getUserFriendlyMessage())
                ->toBe('Questa applicazione non è autorizzata ad accedere al tuo account.');

            $unsupportedResponseType = OAuth2AuthorizationException::unsupportedResponseType();
            expect($unsupportedResponseType->getUserFriendlyMessage())
                ->toBe('Metodo di autorizzazione non supportato. Contatta il supporto tecnico.');

            $invalidScope = OAuth2AuthorizationException::invalidScope();
            expect($invalidScope->getUserFriendlyMessage())
                ->toBe('I permessi richiesti non sono disponibili.');

            $serverError = OAuth2AuthorizationException::serverError();
            expect($serverError->getUserFriendlyMessage())
                ->toBe('Si è verificato un errore temporaneo del server. Riprova tra qualche istante.');

            $temporarilyUnavailable = OAuth2AuthorizationException::temporarilyUnavailable();
            expect($temporarilyUnavailable->getUserFriendlyMessage())
                ->toBe('Il servizio è temporaneamente non disponibile. Riprova più tardi.');
        });

        test('getUserFriendlyMessage returns default translation for unknown errors', function () {
            App::setLocale('en');

            // Create exception with unknown error code
            $unknownError = new OAuth2AuthorizationException(
                'Unknown error',
                400,
                null,
                'unknown_error_code',
                'Unknown error description'
            );

            expect($unknownError->getUserFriendlyMessage())
                ->toBe('An authorization error occurred. Please try again or contact support.');

            App::setLocale('it');
            expect($unknownError->getUserFriendlyMessage())
                ->toBe('Si è verificato un errore di autorizzazione. Riprova o contatta il supporto tecnico.');
        });
    });

    describe('OAuth2TokenExchangeException Translation', function () {
        test('getUserFriendlyMessage returns English translations', function () {
            App::setLocale('en');

            $invalidCode = OAuth2TokenExchangeException::invalidCode();
            expect($invalidCode->getUserFriendlyMessage())
                ->toBe('The authorization code has expired or is invalid. Please restart the authorization process.');

            $invalidClientCredentials = OAuth2TokenExchangeException::invalidClientCredentials();
            expect($invalidClientCredentials->getUserFriendlyMessage())
                ->toBe('Application credentials are invalid. Please contact support.');

            $networkFailure = OAuth2TokenExchangeException::networkFailure();
            expect($networkFailure->getUserFriendlyMessage())
                ->toBe('Network connection failed. Please check your connection and try again.');
        });

        test('getUserFriendlyMessage returns Italian translations', function () {
            App::setLocale('it');

            $invalidCode = OAuth2TokenExchangeException::invalidCode();
            expect($invalidCode->getUserFriendlyMessage())
                ->toBe('Il codice di autorizzazione è scaduto o non valido. Riavvia il processo di autorizzazione.');

            $invalidClientCredentials = OAuth2TokenExchangeException::invalidClientCredentials();
            expect($invalidClientCredentials->getUserFriendlyMessage())
                ->toBe('Le credenziali dell\'applicazione non sono valide. Contatta il supporto tecnico.');

            $networkFailure = OAuth2TokenExchangeException::networkFailure();
            expect($networkFailure->getUserFriendlyMessage())
                ->toBe('Connessione di rete fallita. Controlla la tua connessione e riprova.');
        });

        test('getUserFriendlyMessage returns default translation for unknown errors', function () {
            App::setLocale('en');

            $unknownError = new OAuth2TokenExchangeException(
                'Unknown error',
                400,
                null,
                'unknown_error_code',
                'Unknown error description'
            );

            expect($unknownError->getUserFriendlyMessage())
                ->toBe('A token exchange error occurred. Please try again or contact support.');

            App::setLocale('it');
            expect($unknownError->getUserFriendlyMessage())
                ->toBe('Si è verificato un errore nello scambio del token. Riprova o contatta il supporto tecnico.');
        });
    });

    describe('OAuth2TokenRefreshException Translation', function () {
        test('getUserFriendlyMessage returns English translations', function () {
            App::setLocale('en');

            $invalidRefreshToken = OAuth2TokenRefreshException::invalidRefreshToken();
            expect($invalidRefreshToken->getUserFriendlyMessage())
                ->toBe('Your session has expired. Please log in again.');

            $clientAuthenticationFailed = OAuth2TokenRefreshException::clientAuthenticationFailed();
            expect($clientAuthenticationFailed->getUserFriendlyMessage())
                ->toBe('Authentication failed. Please try logging in again.');

            $tokenRevoked = OAuth2TokenRefreshException::tokenRevoked();
            expect($tokenRevoked->getUserFriendlyMessage())
                ->toBe('Your access has been revoked. Please log in again.');
        });

        test('getUserFriendlyMessage returns Italian translations', function () {
            App::setLocale('it');

            $invalidRefreshToken = OAuth2TokenRefreshException::invalidRefreshToken();
            expect($invalidRefreshToken->getUserFriendlyMessage())
                ->toBe('La tua sessione è scaduta. Effettua nuovamente l\'accesso.');

            $clientAuthenticationFailed = OAuth2TokenRefreshException::clientAuthenticationFailed();
            expect($clientAuthenticationFailed->getUserFriendlyMessage())
                ->toBe('Autenticazione fallita. Prova ad effettuare nuovamente l\'accesso.');

            $tokenRevoked = OAuth2TokenRefreshException::tokenRevoked();
            expect($tokenRevoked->getUserFriendlyMessage())
                ->toBe('Il tuo accesso è stato revocato. Effettua nuovamente l\'accesso.');
        });

        test('getUserFriendlyMessage returns default translation for unknown errors', function () {
            App::setLocale('en');

            $unknownError = new OAuth2TokenRefreshException(
                'Unknown error',
                400,
                null,
                'unknown_error_code',
                'Unknown error description'
            );

            expect($unknownError->getUserFriendlyMessage())
                ->toBe('A token refresh error occurred. Please log in again.');

            App::setLocale('it');
            expect($unknownError->getUserFriendlyMessage())
                ->toBe('Si è verificato un errore nel rinnovo del token. Effettua nuovamente l\'accesso.');
        });
    });

    describe('OAuth2ConfigurationException Translation', function () {
        test('getUserFriendlyMessage returns English translations', function () {
            App::setLocale('en');

            $missingConfiguration = OAuth2ConfigurationException::missingConfiguration('client_id');
            expect($missingConfiguration->getUserFriendlyMessage())
                ->toBe('Application configuration is incomplete. Please contact support.');

            $invalidRedirectUrl = OAuth2ConfigurationException::invalidRedirectUrl();
            expect($invalidRedirectUrl->getUserFriendlyMessage())
                ->toBe('Invalid redirect URL configuration. Please contact support.');

            $malformedConfiguration = OAuth2ConfigurationException::malformedConfiguration();
            expect($malformedConfiguration->getUserFriendlyMessage())
                ->toBe('Application configuration error. Please contact support.');
        });

        test('getUserFriendlyMessage returns Italian translations', function () {
            App::setLocale('it');

            $missingConfiguration = OAuth2ConfigurationException::missingConfiguration('client_id');
            expect($missingConfiguration->getUserFriendlyMessage())
                ->toBe('La configurazione dell\'applicazione è incompleta. Contatta il supporto tecnico.');

            $invalidRedirectUrl = OAuth2ConfigurationException::invalidRedirectUrl();
            expect($invalidRedirectUrl->getUserFriendlyMessage())
                ->toBe('Configurazione URL di reindirizzamento non valida. Contatta il supporto tecnico.');

            $malformedConfiguration = OAuth2ConfigurationException::malformedConfiguration();
            expect($malformedConfiguration->getUserFriendlyMessage())
                ->toBe('Errore nella configurazione dell\'applicazione. Contatta il supporto tecnico.');
        });

        test('getUserFriendlyMessage returns default translation for unknown errors', function () {
            App::setLocale('en');

            $unknownError = new OAuth2ConfigurationException(
                'Unknown error',
                500,
                null,
                'unknown_error_code',
                'Unknown error description'
            );

            expect($unknownError->getUserFriendlyMessage())
                ->toBe('A configuration error occurred. Please contact support.');

            App::setLocale('it');
            expect($unknownError->getUserFriendlyMessage())
                ->toBe('Si è verificato un errore di configurazione. Contatta il supporto tecnico.');
        });
    });

    describe('Cross-Exception Translation Consistency', function () {
        test('all exception classes properly implement getUserFriendlyMessage', function () {
            $exceptionClasses = [
                OAuth2AuthorizationException::class,
                OAuth2TokenExchangeException::class,
                OAuth2TokenRefreshException::class,
                OAuth2ConfigurationException::class,
            ];

            foreach ($exceptionClasses as $exceptionClass) {
                expect(method_exists($exceptionClass, 'getUserFriendlyMessage'))
                    ->toBeTrue("Class {$exceptionClass} should implement getUserFriendlyMessage");
            }
        });

        test('exception messages are properly translated and non-empty', function () {
            $exceptions = [
                OAuth2AuthorizationException::accessDenied(),
                OAuth2TokenExchangeException::invalidCode(),
                OAuth2TokenRefreshException::invalidRefreshToken(),
                OAuth2ConfigurationException::missingConfiguration('test_config'),
            ];

            foreach (['en', 'it'] as $locale) {
                App::setLocale($locale);

                foreach ($exceptions as $exception) {
                    $message = $exception->getUserFriendlyMessage();
                    expect($message)->not->toBeEmpty(
                        'Empty message for '.get_class($exception)." in locale {$locale}"
                    );
                    expect(str_contains($message, 'fatture-in-cloud::'))
                        ->toBeFalse('Translation key not resolved for '.get_class($exception)." in locale {$locale}");
                }
            }
        });
    });
});
