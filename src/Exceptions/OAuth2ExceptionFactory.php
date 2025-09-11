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
    public static function fromOAuth2Error(OAuth2Error $oauth2Error): OAuth2AuthorizationException|OAuth2TokenExchangeException|OAuth2TokenRefreshException|OAuth2ConfigurationException
    {
        $error = $oauth2Error->getError();

        return match ($error) {
            // Authorization errors
            OAuth2Exception::ACCESS_DENIED => OAuth2AuthorizationException::accessDenied($oauth2Error->getErrorDescription()),
            OAuth2Exception::INVALID_REQUEST => OAuth2AuthorizationException::invalidRequest($oauth2Error->getErrorDescription()),
            'unauthorized_client' => OAuth2AuthorizationException::unauthorizedClient($oauth2Error->getErrorDescription()),
            'unsupported_response_type' => OAuth2AuthorizationException::unsupportedResponseType($oauth2Error->getErrorDescription()),
            'invalid_scope' => OAuth2AuthorizationException::invalidScope($oauth2Error->getErrorDescription()),
            OAuth2Exception::SERVER_ERROR => OAuth2AuthorizationException::serverError($oauth2Error->getErrorDescription()),
            OAuth2Exception::TEMPORARILY_UNAVAILABLE => OAuth2AuthorizationException::temporarilyUnavailable($oauth2Error->getErrorDescription()),

            // Token exchange errors
            'invalid_code' => OAuth2TokenExchangeException::invalidCode($oauth2Error->getErrorDescription()),
            'invalid_client_credentials' => OAuth2TokenExchangeException::invalidClientCredentials($oauth2Error->getErrorDescription()),
            'network_failure' => OAuth2TokenExchangeException::networkFailure($oauth2Error->getErrorDescription()),

            // Token refresh errors
            'invalid_refresh_token' => OAuth2TokenRefreshException::invalidRefreshToken($oauth2Error->getErrorDescription()),
            'client_authentication_failed' => OAuth2TokenRefreshException::clientAuthenticationFailed($oauth2Error->getErrorDescription()),
            'token_revoked' => OAuth2TokenRefreshException::tokenRevoked($oauth2Error->getErrorDescription()),

            // Configuration errors
            'missing_configuration' => OAuth2ConfigurationException::missingConfiguration('unknown_config', $oauth2Error->getErrorDescription()),
            'invalid_redirect_url' => OAuth2ConfigurationException::invalidRedirectUrl(null, $oauth2Error->getErrorDescription()),
            'malformed_configuration' => OAuth2ConfigurationException::malformedConfiguration($oauth2Error->getErrorDescription()),

            // Default to authorization error for unknown errors
            default => OAuth2AuthorizationException::invalidRequest("Unknown OAuth2 error: {$error}. {$oauth2Error->getErrorDescription()}"),
        };
    }

    // Authorization exceptions
    public static function accessDenied(?string $description = null): OAuth2AuthorizationException
    {
        return OAuth2AuthorizationException::accessDenied($description);
    }

    public static function invalidRequest(?string $description = null): OAuth2AuthorizationException
    {
        return OAuth2AuthorizationException::invalidRequest($description);
    }

    public static function unauthorizedClient(?string $description = null): OAuth2AuthorizationException
    {
        return OAuth2AuthorizationException::unauthorizedClient($description);
    }

    public static function unsupportedResponseType(?string $description = null): OAuth2AuthorizationException
    {
        return OAuth2AuthorizationException::unsupportedResponseType($description);
    }

    public static function invalidScope(?string $description = null): OAuth2AuthorizationException
    {
        return OAuth2AuthorizationException::invalidScope($description);
    }

    public static function serverError(?string $description = null): OAuth2AuthorizationException
    {
        return OAuth2AuthorizationException::serverError($description);
    }

    public static function temporarilyUnavailable(?string $description = null): OAuth2AuthorizationException
    {
        return OAuth2AuthorizationException::temporarilyUnavailable($description);
    }

    // Token exchange exceptions
    public static function invalidCode(?string $description = null): OAuth2TokenExchangeException
    {
        return OAuth2TokenExchangeException::invalidCode($description);
    }

    public static function invalidClientCredentials(?string $description = null): OAuth2TokenExchangeException
    {
        return OAuth2TokenExchangeException::invalidClientCredentials($description);
    }

    public static function networkFailure(?string $description = null, ?Exception $previous = null): OAuth2TokenExchangeException
    {
        return OAuth2TokenExchangeException::networkFailure($description, $previous);
    }

    // Token refresh exceptions
    public static function invalidRefreshToken(?string $description = null): OAuth2TokenRefreshException
    {
        return OAuth2TokenRefreshException::invalidRefreshToken($description);
    }

    public static function clientAuthenticationFailed(?string $description = null): OAuth2TokenRefreshException
    {
        return OAuth2TokenRefreshException::clientAuthenticationFailed($description);
    }

    public static function tokenRevoked(?string $description = null): OAuth2TokenRefreshException
    {
        return OAuth2TokenRefreshException::tokenRevoked($description);
    }

    // Configuration exceptions
    public static function missingConfiguration(string $configKey, ?string $description = null): OAuth2ConfigurationException
    {
        return OAuth2ConfigurationException::missingConfiguration($configKey, $description);
    }

    public static function invalidRedirectUrl(?string $url = null, ?string $description = null): OAuth2ConfigurationException
    {
        return OAuth2ConfigurationException::invalidRedirectUrl($url, $description);
    }

    public static function malformedConfiguration(?string $description = null): OAuth2ConfigurationException
    {
        return OAuth2ConfigurationException::malformedConfiguration($description);
    }
}
