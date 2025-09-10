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

    public static function fromOAuth2Error(OAuth2Error $oauth2Error): AuthorizationException|TokenExchangeException|TokenRefreshException|ConfigurationException
    {
        $error = $oauth2Error->getError();

        return match ($error) {
            // Authorization errors
            self::ACCESS_DENIED, self::INVALID_REQUEST, 'unauthorized_client',
            'unsupported_response_type', 'invalid_scope',
            self::SERVER_ERROR, self::TEMPORARILY_UNAVAILABLE => AuthorizationException::fromOAuth2Error($oauth2Error),

            // Token exchange errors
            'invalid_code', 'invalid_client_credentials', 'network_failure' => TokenExchangeException::fromOAuth2Error($oauth2Error),

            // Token refresh errors
            'invalid_refresh_token', 'client_authentication_failed', 'token_revoked' => TokenRefreshException::fromOAuth2Error($oauth2Error),

            // Configuration errors
            'missing_configuration', 'invalid_redirect_url', 'malformed_configuration' => ConfigurationException::fromOAuth2Error($oauth2Error),

            // Default to authorization for unknown errors
            default => AuthorizationException::fromOAuth2Error($oauth2Error),
        };
    }

    // Backward-compatible factory methods (delegates to specific classes)
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
