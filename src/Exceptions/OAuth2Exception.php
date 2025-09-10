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

/**
 * Base OAuth2 Exception with core functionality and factory methods
 */
abstract class OAuth2Exception extends Exception
{
    // Core error codes (shared across all categories)
    public const ACCESS_DENIED = 'access_denied';

    public const INVALID_REQUEST = 'invalid_request';

    public const SERVER_ERROR = 'server_error';

    public const TEMPORARILY_UNAVAILABLE = 'temporarily_unavailable';

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Exception $previous = null,
        protected ?string $error = null,
        protected ?string $errorDescription = null,
        protected OAuth2ErrorCategory $category = OAuth2ErrorCategory::AUTHORIZATION,
        protected bool $isRetryable = false,
        protected array $context = []
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

    abstract public function getUserFriendlyMessage(): string;

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

    protected function sanitizeContextForLogging(array $context): array
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

    /**
     * Create OAuth2 exception from OAuth2Error response
     *
     * @deprecated Use OAuth2ExceptionFactory::fromOAuth2Error() instead
     */
    public static function fromOAuth2Error(OAuth2Error $oauth2Error): AuthorizationException|TokenExchangeException|TokenRefreshException|ConfigurationException
    {
        return OAuth2ExceptionFactory::fromOAuth2Error($oauth2Error);
    }

    // Essential factory methods for backward compatibility
    // Most factory methods moved to OAuth2ExceptionFactory to reduce method count

    /**
     * @deprecated Use OAuth2ExceptionFactory::accessDenied() instead
     */
    public static function accessDenied(?string $description = null): AuthorizationException
    {
        return OAuth2ExceptionFactory::accessDenied($description);
    }

    /**
     * @deprecated Use OAuth2ExceptionFactory::invalidRequest() instead
     */
    public static function invalidRequest(?string $description = null): AuthorizationException
    {
        return OAuth2ExceptionFactory::invalidRequest($description);
    }

    /**
     * @deprecated Use OAuth2ExceptionFactory::serverError() instead
     */
    public static function serverError(?string $description = null): AuthorizationException
    {
        return OAuth2ExceptionFactory::serverError($description);
    }

    /**
     * @deprecated Use OAuth2ExceptionFactory::temporarilyUnavailable() instead
     */
    public static function temporarilyUnavailable(?string $description = null): AuthorizationException
    {
        return OAuth2ExceptionFactory::temporarilyUnavailable($description);
    }

    /**
     * @deprecated Use OAuth2ExceptionFactory::networkFailure() instead
     */
    public static function networkFailure(?string $description = null, ?Exception $previous = null): TokenExchangeException
    {
        return OAuth2ExceptionFactory::networkFailure($description, $previous);
    }

    /**
     * @deprecated Use OAuth2ExceptionFactory::missingConfiguration() instead
     */
    public static function missingConfiguration(string $configKey, ?string $description = null): ConfigurationException
    {
        return OAuth2ExceptionFactory::missingConfiguration($configKey, $description);
    }
}
