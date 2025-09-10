<?php

namespace Codeman\FattureInCloud\Exceptions;

use Exception;
use FattureInCloud\OAuth2\OAuth2Error;

enum OAuth2ErrorCategory: string
{
    case AUTHORIZATION = 'authorization';
    case TOKEN_EXCHANGE = 'token_exchange';
    case TOKEN_REFRESH = 'token_refresh';
    case CONFIGURATION = 'configuration';
}

class OAuth2Exception extends Exception
{
    // Standard OAuth2 error codes
    public const ACCESS_DENIED = 'access_denied';

    public const INVALID_REQUEST = 'invalid_request';

    public const UNAUTHORIZED_CLIENT = 'unauthorized_client';

    public const UNSUPPORTED_RESPONSE_TYPE = 'unsupported_response_type';

    public const INVALID_SCOPE = 'invalid_scope';

    public const SERVER_ERROR = 'server_error';

    public const TEMPORARILY_UNAVAILABLE = 'temporarily_unavailable';

    // Token exchange error codes
    public const INVALID_CODE = 'invalid_code';

    public const INVALID_CLIENT_CREDENTIALS = 'invalid_client_credentials';

    public const NETWORK_FAILURE = 'network_failure';

    // Token refresh error codes
    public const INVALID_REFRESH_TOKEN = 'invalid_refresh_token';

    public const CLIENT_AUTHENTICATION_FAILED = 'client_authentication_failed';

    public const TOKEN_REVOKED = 'token_revoked';

    // Configuration error codes
    public const MISSING_CONFIGURATION = 'missing_configuration';

    public const INVALID_REDIRECT_URL = 'invalid_redirect_url';

    public const MALFORMED_CONFIGURATION = 'malformed_configuration';

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Exception $previous = null,
        private ?string $error = null,
        private ?string $errorDescription = null,
        private OAuth2ErrorCategory $category = OAuth2ErrorCategory::AUTHORIZATION,
        private bool $isRetryable = false,
        private array $context = []
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function getErrorDescription(): ?string
    {
        return $this->errorDescription;
    }

    public function getCategory(): OAuth2ErrorCategory
    {
        return $this->category;
    }

    public function isRetryable(): bool
    {
        return $this->isRetryable;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getUserFriendlyMessage(): string
    {
        return match ($this->error) {
            self::ACCESS_DENIED => 'Authorization was cancelled. Please try again if you want to connect your account.',
            self::INVALID_REQUEST => 'There was a problem with the authorization request. Please contact support.',
            self::UNAUTHORIZED_CLIENT => 'This application is not authorized to access your account.',
            self::UNSUPPORTED_RESPONSE_TYPE => 'Authorization method not supported. Please contact support.',
            self::INVALID_SCOPE => 'The requested permissions are not available.',
            self::SERVER_ERROR => 'A temporary server error occurred. Please try again in a moment.',
            self::TEMPORARILY_UNAVAILABLE => 'The service is temporarily unavailable. Please try again later.',
            self::INVALID_CODE => 'The authorization code has expired or is invalid. Please restart the authorization process.',
            self::INVALID_CLIENT_CREDENTIALS => 'Application credentials are invalid. Please contact support.',
            self::NETWORK_FAILURE => 'Network connection failed. Please check your connection and try again.',
            self::INVALID_REFRESH_TOKEN => 'Your session has expired. Please log in again.',
            self::CLIENT_AUTHENTICATION_FAILED => 'Authentication failed. Please try logging in again.',
            self::TOKEN_REVOKED => 'Your access has been revoked. Please log in again.',
            self::MISSING_CONFIGURATION => 'Application configuration is incomplete. Please contact support.',
            self::INVALID_REDIRECT_URL => 'Invalid redirect URL configuration. Please contact support.',
            self::MALFORMED_CONFIGURATION => 'Application configuration error. Please contact support.',
            default => 'An authentication error occurred. Please try again or contact support.',
        };
    }

    public function getLoggingContext(): array
    {
        return [
            'oauth2_error' => $this->error,
            'error_description' => $this->errorDescription,
            'category' => $this->category->value,
            'is_retryable' => $this->isRetryable,
            'http_code' => $this->code,
            'context' => $this->sanitizeContextForLogging($this->context),
        ];
    }

    private function sanitizeContextForLogging(array $context): array
    {
        $sanitized = [];
        $sensitiveKeys = ['client_secret', 'access_token', 'refresh_token', 'authorization_code', 'password'];

        foreach ($context as $key => $value) {
            if (in_array(strtolower($key), $sensitiveKeys)) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeContextForLogging($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    public static function fromOAuth2Error(OAuth2Error $oauth2Error): self
    {
        $message = "OAuth2 error: {$oauth2Error->getError()}";
        if ($oauth2Error->getErrorDescription()) {
            $message .= " - {$oauth2Error->getErrorDescription()}";
        }

        $category = match ($oauth2Error->getError()) {
            self::ACCESS_DENIED, self::INVALID_REQUEST, self::UNAUTHORIZED_CLIENT,
            self::UNSUPPORTED_RESPONSE_TYPE, self::INVALID_SCOPE,
            self::SERVER_ERROR, self::TEMPORARILY_UNAVAILABLE => OAuth2ErrorCategory::AUTHORIZATION,
            self::INVALID_CODE, self::INVALID_CLIENT_CREDENTIALS => OAuth2ErrorCategory::TOKEN_EXCHANGE,
            self::INVALID_REFRESH_TOKEN, self::CLIENT_AUTHENTICATION_FAILED,
            self::TOKEN_REVOKED => OAuth2ErrorCategory::TOKEN_REFRESH,
            default => OAuth2ErrorCategory::AUTHORIZATION,
        };

        $isRetryable = in_array($oauth2Error->getError(), [self::SERVER_ERROR, self::TEMPORARILY_UNAVAILABLE]);

        return new self(
            $message,
            $oauth2Error->getCode(),
            null,
            $oauth2Error->getError(),
            $oauth2Error->getErrorDescription(),
            $category,
            $isRetryable
        );
    }

    // Authorization error factory methods
    public static function accessDenied(?string $description = null): self
    {
        return new self(
            'User denied authorization access',
            401,
            null,
            self::ACCESS_DENIED,
            $description ?? 'The user denied the authorization request',
            OAuth2ErrorCategory::AUTHORIZATION,
            false,
            ['http_status' => 401, 'user_action' => 'cancelled']
        );
    }

    public static function invalidRequest(?string $description = null): self
    {
        return new self(
            'Invalid OAuth2 authorization request',
            400,
            null,
            self::INVALID_REQUEST,
            $description ?? 'The request is missing a required parameter or is otherwise malformed',
            OAuth2ErrorCategory::AUTHORIZATION,
            false,
            ['http_status' => 400]
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
            OAuth2ErrorCategory::AUTHORIZATION,
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
            OAuth2ErrorCategory::AUTHORIZATION,
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
            OAuth2ErrorCategory::AUTHORIZATION,
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
            OAuth2ErrorCategory::AUTHORIZATION,
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
            OAuth2ErrorCategory::AUTHORIZATION,
            true,
            ['http_status' => 503, 'retry_after' => 60]
        );
    }

    // Token exchange error factory methods
    public static function invalidCode(?string $description = null): self
    {
        return new self(
            'Invalid or expired authorization code',
            400,
            null,
            self::INVALID_CODE,
            $description ?? 'The authorization code is invalid, expired, or has already been used',
            OAuth2ErrorCategory::TOKEN_EXCHANGE,
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
            OAuth2ErrorCategory::TOKEN_EXCHANGE,
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
            OAuth2ErrorCategory::TOKEN_EXCHANGE,
            true,
            ['retry_after' => 10, 'max_retries' => 3]
        );
    }

    // Token refresh error factory methods
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

    // Configuration error factory methods
    public static function missingConfiguration(string $configKey, ?string $description = null): self
    {
        return new self(
            "Missing OAuth2 configuration: {$configKey}",
            500,
            null,
            self::MISSING_CONFIGURATION,
            $description ?? "Required configuration parameter '{$configKey}' is missing",
            OAuth2ErrorCategory::CONFIGURATION,
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
            OAuth2ErrorCategory::CONFIGURATION,
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
            OAuth2ErrorCategory::CONFIGURATION,
            false,
            ['http_status' => 500, 'action' => 'validate_configuration']
        );
    }
}
