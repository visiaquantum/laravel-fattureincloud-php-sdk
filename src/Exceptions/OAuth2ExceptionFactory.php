<?php

namespace Codeman\FattureInCloud\Exceptions;

use Exception;
use FattureInCloud\OAuth2\OAuth2Error;

/**
 * Factory class for creating OAuth2 exceptions
 * Separates factory methods from the base exception to comply with SonarQube method limits
 */
class OAuth2ExceptionFactory
{
    public static function fromOAuth2Error(OAuth2Error $oauth2Error): AuthorizationException|TokenExchangeException|TokenRefreshException|ConfigurationException
    {
        $error = $oauth2Error->getError();

        return match ($error) {
            // Authorization errors
            OAuth2Exception::ACCESS_DENIED => AuthorizationException::accessDenied($oauth2Error->getErrorDescription()),
            OAuth2Exception::INVALID_REQUEST => AuthorizationException::invalidRequest($oauth2Error->getErrorDescription()),
            'unauthorized_client' => AuthorizationException::unauthorizedClient($oauth2Error->getErrorDescription()),
            'unsupported_response_type' => AuthorizationException::unsupportedResponseType($oauth2Error->getErrorDescription()),
            'invalid_scope' => AuthorizationException::invalidScope($oauth2Error->getErrorDescription()),
            OAuth2Exception::SERVER_ERROR => AuthorizationException::serverError($oauth2Error->getErrorDescription()),
            OAuth2Exception::TEMPORARILY_UNAVAILABLE => AuthorizationException::temporarilyUnavailable($oauth2Error->getErrorDescription()),

            // Token exchange errors
            'invalid_code' => TokenExchangeException::invalidCode($oauth2Error->getErrorDescription()),
            'invalid_client_credentials' => TokenExchangeException::invalidClientCredentials($oauth2Error->getErrorDescription()),
            'network_failure' => TokenExchangeException::networkFailure($oauth2Error->getErrorDescription()),

            // Token refresh errors
            'invalid_refresh_token' => TokenRefreshException::invalidRefreshToken($oauth2Error->getErrorDescription()),
            'client_authentication_failed' => TokenRefreshException::clientAuthenticationFailed($oauth2Error->getErrorDescription()),
            'token_revoked' => TokenRefreshException::tokenRevoked($oauth2Error->getErrorDescription()),

            // Configuration errors
            'missing_configuration' => ConfigurationException::missingConfiguration('unknown_config', $oauth2Error->getErrorDescription()),
            'invalid_redirect_url' => ConfigurationException::invalidRedirectUrl(null, $oauth2Error->getErrorDescription()),
            'malformed_configuration' => ConfigurationException::malformedConfiguration($oauth2Error->getErrorDescription()),

            // Default to authorization error for unknown errors
            default => AuthorizationException::invalidRequest("Unknown OAuth2 error: {$error}. {$oauth2Error->getErrorDescription()}"),
        };
    }

    // Authorization exceptions
    public static function accessDenied(?string $description = null): AuthorizationException
    {
        return AuthorizationException::accessDenied($description);
    }

    public static function invalidRequest(?string $description = null): AuthorizationException
    {
        return AuthorizationException::invalidRequest($description);
    }

    public static function unauthorizedClient(?string $description = null): AuthorizationException
    {
        return AuthorizationException::unauthorizedClient($description);
    }

    public static function unsupportedResponseType(?string $description = null): AuthorizationException
    {
        return AuthorizationException::unsupportedResponseType($description);
    }

    public static function invalidScope(?string $description = null): AuthorizationException
    {
        return AuthorizationException::invalidScope($description);
    }

    public static function serverError(?string $description = null): AuthorizationException
    {
        return AuthorizationException::serverError($description);
    }

    public static function temporarilyUnavailable(?string $description = null): AuthorizationException
    {
        return AuthorizationException::temporarilyUnavailable($description);
    }

    // Token exchange exceptions
    public static function invalidCode(?string $description = null): TokenExchangeException
    {
        return TokenExchangeException::invalidCode($description);
    }

    public static function invalidClientCredentials(?string $description = null): TokenExchangeException
    {
        return TokenExchangeException::invalidClientCredentials($description);
    }

    public static function networkFailure(?string $description = null, ?Exception $previous = null): TokenExchangeException
    {
        return TokenExchangeException::networkFailure($description, $previous);
    }

    // Token refresh exceptions
    public static function invalidRefreshToken(?string $description = null): TokenRefreshException
    {
        return TokenRefreshException::invalidRefreshToken($description);
    }

    public static function clientAuthenticationFailed(?string $description = null): TokenRefreshException
    {
        return TokenRefreshException::clientAuthenticationFailed($description);
    }

    public static function tokenRevoked(?string $description = null): TokenRefreshException
    {
        return TokenRefreshException::tokenRevoked($description);
    }

    // Configuration exceptions
    public static function missingConfiguration(string $configKey, ?string $description = null): ConfigurationException
    {
        return ConfigurationException::missingConfiguration($configKey, $description);
    }

    public static function invalidRedirectUrl(?string $url = null, ?string $description = null): ConfigurationException
    {
        return ConfigurationException::invalidRedirectUrl($url, $description);
    }

    public static function malformedConfiguration(?string $description = null): ConfigurationException
    {
        return ConfigurationException::malformedConfiguration($description);
    }
}
