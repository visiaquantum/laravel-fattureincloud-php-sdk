<?php

namespace Codeman\FattureInCloud\Exceptions;

use FattureInCloud\OAuth2\OAuth2Error;

/**
 * OAuth2 Configuration-specific exceptions
 * Handles errors related to application configuration
 */
class ConfigurationException extends OAuth2Exception
{
    // Configuration error codes
    public const MISSING_CONFIGURATION = 'missing_configuration';

    public const INVALID_REDIRECT_URL = 'invalid_redirect_url';

    public const MALFORMED_CONFIGURATION = 'malformed_configuration';

    public function getUserFriendlyMessage(): string
    {
        return match ($this->error) {
            self::MISSING_CONFIGURATION => 'Application configuration is incomplete. Please contact support.',
            self::INVALID_REDIRECT_URL => 'Invalid redirect URL configuration. Please contact support.',
            self::MALFORMED_CONFIGURATION => 'Application configuration error. Please contact support.',
            default => 'A configuration error occurred. Please contact support.',
        };
    }

    public static function fromOAuth2Error(OAuth2Error $oauth2Error): self
    {
        $message = "OAuth2 configuration error: {$oauth2Error->getError()}";
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

    public static function missingConfiguration(string $configKey, ?string $description = null): self
    {
        return new self(
            "Missing OAuth2 configuration: {$configKey}",
            500,
            null,
            self::MISSING_CONFIGURATION,
            $description ?? "Required configuration parameter '{$configKey}' is missing",
            false,
            ['http_status' => 500, 'missing_config' => $configKey, 'action' => 'check_environment']
        );
    }

    public static function invalidRedirectUrl(?string $url = null, ?string $description = null): self
    {
        return new self(
            'Invalid OAuth2 redirect URL configuration',
            500,
            null,
            self::INVALID_REDIRECT_URL,
            $description ?? 'The configured redirect URL is invalid or malformed',
            false,
            ['http_status' => 500, 'invalid_url' => $url, 'action' => 'check_configuration']
        );
    }

    public static function malformedConfiguration(?string $description = null): self
    {
        return new self(
            'Malformed OAuth2 configuration',
            500,
            null,
            self::MALFORMED_CONFIGURATION,
            $description ?? 'The OAuth2 configuration contains invalid or malformed values',
            false,
            ['http_status' => 500, 'action' => 'validate_configuration']
        );
    }
}
