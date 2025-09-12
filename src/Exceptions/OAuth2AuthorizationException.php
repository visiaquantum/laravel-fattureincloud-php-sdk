<?php

namespace Codeman\FattureInCloud\Exceptions;

use FattureInCloud\OAuth2\OAuth2Error;

/**
 * OAuth2 Authorization-specific exceptions
 * Handles errors during the authorization flow
 */
class OAuth2AuthorizationException extends OAuth2Exception
{
    // Authorization-specific error codes
    public const UNAUTHORIZED_CLIENT = 'unauthorized_client';

    public const UNSUPPORTED_RESPONSE_TYPE = 'unsupported_response_type';

    public const INVALID_SCOPE = 'invalid_scope';

    public function getUserFriendlyMessage(): string
    {
        return match ($this->error) {
            self::ACCESS_DENIED => __('fatture-in-cloud::fatture-in-cloud.oauth2.authorization.access_denied'),
            self::INVALID_REQUEST => __('fatture-in-cloud::fatture-in-cloud.oauth2.authorization.invalid_request'),
            self::UNAUTHORIZED_CLIENT => __('fatture-in-cloud::fatture-in-cloud.oauth2.authorization.unauthorized_client'),
            self::UNSUPPORTED_RESPONSE_TYPE => __('fatture-in-cloud::fatture-in-cloud.oauth2.authorization.unsupported_response_type'),
            self::INVALID_SCOPE => __('fatture-in-cloud::fatture-in-cloud.oauth2.authorization.invalid_scope'),
            self::SERVER_ERROR => __('fatture-in-cloud::fatture-in-cloud.oauth2.authorization.server_error'),
            self::TEMPORARILY_UNAVAILABLE => __('fatture-in-cloud::fatture-in-cloud.oauth2.authorization.temporarily_unavailable'),
            default => __('fatture-in-cloud::fatture-in-cloud.oauth2.authorization.default'),
        };
    }

    public static function fromOAuth2Error(OAuth2Error $oauth2Error): self
    {
        $message = "OAuth2 authorization error: {$oauth2Error->getError()}";
        if ($oauth2Error->getErrorDescription()) {
            $message .= " - {$oauth2Error->getErrorDescription()}";
        }

        $isRetryable = in_array($oauth2Error->getError(), [self::SERVER_ERROR, self::TEMPORARILY_UNAVAILABLE]);

        return new self(
            $message,
            $oauth2Error->getCode(),
            null,
            $oauth2Error->getError(),
            $oauth2Error->getErrorDescription(),
            $isRetryable
        );
    }

    public static function accessDenied(?string $description = null): self
    {
        return new self(
            'User denied authorization access',
            401,
            null,
            self::ACCESS_DENIED,
            $description ?? 'The user denied the authorization request',
            false,
            ['http_status' => 401, 'user_action' => 'cancelled']
        );
    }

    public static function invalidRequest(?string $description = null, ?array $context = null): self
    {
        return new self(
            'Invalid OAuth2 authorization request',
            400,
            null,
            self::INVALID_REQUEST,
            $description ?? 'The request is missing a required parameter or is otherwise malformed',
            false,
            array_merge(['http_status' => 400], $context ?? [])
        );
    }

    public static function unauthorizedClient(?string $description = null): self
    {
        return new self(
            'Client not authorized for OAuth2',
            401,
            null,
            self::UNAUTHORIZED_CLIENT,
            $description ?? 'The client is not authorized to request an authorization code',
            false,
            ['http_status' => 401]
        );
    }

    public static function unsupportedResponseType(?string $description = null): self
    {
        return new self(
            'Unsupported OAuth2 response type',
            400,
            null,
            self::UNSUPPORTED_RESPONSE_TYPE,
            $description ?? 'The authorization server does not support the response type',
            false,
            ['http_status' => 400]
        );
    }

    public static function invalidScope(?string $description = null): self
    {
        return new self(
            'Invalid OAuth2 scope requested',
            400,
            null,
            self::INVALID_SCOPE,
            $description ?? 'The requested scope is invalid, unknown, or malformed',
            false,
            ['http_status' => 400]
        );
    }

    public static function serverError(?string $description = null): self
    {
        return new self(
            'OAuth2 server error occurred',
            500,
            null,
            self::SERVER_ERROR,
            $description ?? 'The authorization server encountered an unexpected condition',
            true,
            ['http_status' => 500, 'retry_after' => 30]
        );
    }

    public static function temporarilyUnavailable(?string $description = null): self
    {
        return new self(
            'OAuth2 service temporarily unavailable',
            503,
            null,
            self::TEMPORARILY_UNAVAILABLE,
            $description ?? 'The authorization server is temporarily overloaded or under maintenance',
            true,
            ['http_status' => 503, 'retry_after' => 60]
        );
    }
}
