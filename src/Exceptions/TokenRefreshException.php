<?php

namespace Codeman\FattureInCloud\Exceptions;

use FattureInCloud\OAuth2\OAuth2Error;

/**
 * OAuth2 Token Refresh-specific exceptions
 * Handles errors during token refresh operations
 */
class TokenRefreshException extends OAuth2Exception
{
    // Token refresh error codes
    public const INVALID_REFRESH_TOKEN = 'invalid_refresh_token';

    public const CLIENT_AUTHENTICATION_FAILED = 'client_authentication_failed';

    public const TOKEN_REVOKED = 'token_revoked';

    public function getUserFriendlyMessage(): string
    {
        return match ($this->error) {
            self::INVALID_REFRESH_TOKEN => 'Your session has expired. Please log in again.',
            self::CLIENT_AUTHENTICATION_FAILED => 'Authentication failed. Please try logging in again.',
            self::TOKEN_REVOKED => 'Your access has been revoked. Please log in again.',
            default => 'A token refresh error occurred. Please log in again.',
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
            OAuth2ErrorCategory::TOKEN_REFRESH,
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
            OAuth2ErrorCategory::TOKEN_REFRESH,
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
            OAuth2ErrorCategory::TOKEN_REFRESH,
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
            OAuth2ErrorCategory::TOKEN_REFRESH,
            false,
            ['http_status' => 401, 'action' => 'reauthorize']
        );
    }
}
