<?php

namespace Codeman\FattureInCloud\Exceptions;

use Exception;

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
}
