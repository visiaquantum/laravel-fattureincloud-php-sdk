<?php

namespace Codeman\FattureInCloud\Exceptions;

use Exception;
use FattureInCloud\OAuth2\OAuth2Error;

/**
 * OAuth2 Token Exchange-specific exceptions
 * Handles errors during authorization code to token exchange
 */
class TokenExchangeException extends OAuth2Exception
{
    // Token exchange error codes
    public const INVALID_CODE = 'invalid_code';

    public const INVALID_CLIENT_CREDENTIALS = 'invalid_client_credentials';

    public const NETWORK_FAILURE = 'network_failure';

    public function getUserFriendlyMessage(): string
    {
        return match ($this->error) {
            self::INVALID_CODE => 'The authorization code has expired or is invalid. Please restart the authorization process.',
            self::INVALID_CLIENT_CREDENTIALS => 'Application credentials are invalid. Please contact support.',
            self::NETWORK_FAILURE => 'Network connection failed. Please check your connection and try again.',
            default => 'A token exchange error occurred. Please try again or contact support.',
        };
    }

    public static function fromOAuth2Error(OAuth2Error $oauth2Error): self
    {
        $message = "OAuth2 token exchange error: {$oauth2Error->getError()}";
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

    public static function invalidCode(?string $description = null): self
    {
        return new self(
            'Invalid or expired authorization code',
            400,
            null,
            self::INVALID_CODE,
            $description ?? 'The authorization code is invalid, expired, or has already been used',
            false,
            ['http_status' => 400, 'action' => 'restart_authorization']
        );
    }

    public static function invalidClientCredentials(?string $description = null): self
    {
        return new self(
            'Invalid client credentials',
            401,
            null,
            self::INVALID_CLIENT_CREDENTIALS,
            $description ?? 'The client credentials are invalid or missing',
            false,
            ['http_status' => 401, 'action' => 'check_configuration']
        );
    }

    public static function networkFailure(?string $description = null, ?Exception $previous = null): self
    {
        return new self(
            'Network failure during token exchange',
            0,
            $previous,
            self::NETWORK_FAILURE,
            $description ?? 'Network connection failed during OAuth2 token exchange',
            true,
            ['retry_after' => 10, 'max_retries' => 3]
        );
    }
}
