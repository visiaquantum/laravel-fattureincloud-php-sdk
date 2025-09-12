<?php

namespace Codeman\FattureInCloud\Exceptions;

use FattureInCloud\OAuth2\OAuth2Error;

/**
 * OAuth2 Token Refresh-specific exceptions
 * Handles errors during token refresh operations
 */
class OAuth2TokenRefreshException extends OAuth2Exception
{
    // Token refresh error codes
    public const INVALID_REFRESH_TOKEN = 'invalid_refresh_token';

    public const CLIENT_AUTHENTICATION_FAILED = 'client_authentication_failed';

    public const TOKEN_REVOKED = 'token_revoked';

    public function getUserFriendlyMessage(): string
    {
        return match ($this->error) {
            self::INVALID_REFRESH_TOKEN => __('fatture-in-cloud::fatture-in-cloud.oauth2.token_refresh.invalid_refresh_token'),
            self::CLIENT_AUTHENTICATION_FAILED => __('fatture-in-cloud::fatture-in-cloud.oauth2.token_refresh.client_authentication_failed'),
            self::TOKEN_REVOKED => __('fatture-in-cloud::fatture-in-cloud.oauth2.token_refresh.token_revoked'),
            default => __('fatture-in-cloud::fatture-in-cloud.oauth2.token_refresh.default'),
        };
    }

    public static function fromOAuth2Error(OAuth2Error $oauth2Error): self
    {
        $message = "OAuth2 token refresh error: {$oauth2Error->getError()}";
        if ($oauth2Error->getErrorDescription()) {
            $message .= " - {$oauth2Error->getErrorDescription()}";
        }

        return new self(
            $message,
            $oauth2Error->getCode(),
            null,
            $oauth2Error->getError(),
            $oauth2Error->getErrorDescription(),
            false
        );
    }

    public static function invalidRefreshToken(?string $description = null): self
    {
        return new self(
            'Invalid or expired refresh token',
            401,
            null,
            self::INVALID_REFRESH_TOKEN,
            $description ?? 'The refresh token is invalid, expired, or has been revoked',
            false,
            ['http_status' => 401, 'action' => 'reauthorize']
        );
    }

    public static function clientAuthenticationFailed(?string $description = null): self
    {
        return new self(
            'Client authentication failed during token refresh',
            401,
            null,
            self::CLIENT_AUTHENTICATION_FAILED,
            $description ?? 'Client authentication failed during token refresh',
            false,
            ['http_status' => 401, 'action' => 'check_credentials']
        );
    }

    public static function tokenRevoked(?string $description = null): self
    {
        return new self(
            'OAuth2 token has been revoked',
            401,
            null,
            self::TOKEN_REVOKED,
            $description ?? 'The access token has been revoked by the user or authorization server',
            false,
            ['http_status' => 401, 'action' => 'reauthorize']
        );
    }
}
