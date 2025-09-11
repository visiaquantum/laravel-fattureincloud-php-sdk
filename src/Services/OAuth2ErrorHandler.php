<?php

namespace Codeman\FattureInCloud\Services;

use Codeman\FattureInCloud\Exceptions\OAuth2AuthorizationException;
use Codeman\FattureInCloud\Exceptions\OAuth2ConfigurationException;
use Codeman\FattureInCloud\Exceptions\OAuth2Exception;
use Codeman\FattureInCloud\Exceptions\OAuth2ExceptionFactory;
use Codeman\FattureInCloud\Exceptions\OAuth2TokenExchangeException;
use Codeman\FattureInCloud\Exceptions\OAuth2TokenRefreshException;
use Exception;
use FattureInCloud\OAuth2\OAuth2Error;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class OAuth2ErrorHandler
{
    public function handleCallbackError(Request $request): Response
    {
        $error = $request->get('error');
        $errorDescription = $request->get('error_description', 'No description provided');

        $exception = match ($error) {
            OAuth2AuthorizationException::ACCESS_DENIED => OAuth2ExceptionFactory::accessDenied($errorDescription),
            OAuth2AuthorizationException::INVALID_REQUEST => OAuth2ExceptionFactory::invalidRequest($errorDescription),
            OAuth2AuthorizationException::UNAUTHORIZED_CLIENT => OAuth2ExceptionFactory::unauthorizedClient($errorDescription),
            OAuth2AuthorizationException::UNSUPPORTED_RESPONSE_TYPE => OAuth2ExceptionFactory::unsupportedResponseType($errorDescription),
            OAuth2AuthorizationException::INVALID_SCOPE => OAuth2ExceptionFactory::invalidScope($errorDescription),
            OAuth2AuthorizationException::SERVER_ERROR => OAuth2ExceptionFactory::serverError($errorDescription),
            OAuth2AuthorizationException::TEMPORARILY_UNAVAILABLE => OAuth2ExceptionFactory::temporarilyUnavailable($errorDescription),
            default => OAuth2ExceptionFactory::invalidRequest("Unknown OAuth2 error: {$error}. {$errorDescription}"),
        };

        $this->logError($exception, 'OAuth2 callback error occurred', [
            'callback_url' => $request->fullUrl(),
            'error_parameter' => $error,
        ]);

        return $this->createErrorResponse($exception);
    }

    public function handleTokenError(OAuth2Error $oauth2Error): Response
    {
        $exception = OAuth2ExceptionFactory::fromOAuth2Error($oauth2Error);

        $this->logError($exception, 'OAuth2 token exchange error occurred');

        return $this->createErrorResponse($exception);
    }

    public function handleException(Exception $exception): Response
    {
        $oauth2Exception = match (true) {
            $exception instanceof OAuth2Exception => $exception,
            $exception instanceof \InvalidArgumentException => OAuth2ExceptionFactory::invalidRequest($exception->getMessage()),
            $exception instanceof \LogicException => OAuth2ExceptionFactory::missingConfiguration('oauth2_manager', $exception->getMessage()),
            default => OAuth2ExceptionFactory::serverError('An unexpected error occurred: '.$exception->getMessage()),
        };

        $this->logError($oauth2Exception, 'OAuth2 error handled', [
            'original_exception' => get_class($exception),
            'original_message' => $exception->getMessage(),
        ]);

        return $this->createErrorResponse($oauth2Exception);
    }

    public function handleNetworkError(Exception $networkException): Response
    {
        $exception = OAuth2ExceptionFactory::networkFailure(
            'Network connection failed during OAuth2 operation',
            $networkException
        );

        $this->logError($exception, 'OAuth2 network error occurred', [
            'network_error' => $networkException->getMessage(),
        ]);

        return $this->createErrorResponse($exception);
    }

    public function handleConfigurationError(string $configKey, ?string $details = null): Response
    {
        $exception = OAuth2ExceptionFactory::missingConfiguration($configKey, $details);

        $this->logError($exception, 'OAuth2 configuration error detected');

        return $this->createErrorResponse($exception);
    }

    public function createErrorResponse(OAuth2Exception $exception): Response
    {
        $statusCode = $exception->getContext()['http_status'] ?? 400;

        $responseData = [
            'status' => 'error',
            'error' => $exception->getError(),
            'message' => $exception->getMessage(),
            'user_message' => $exception->getUserFriendlyMessage(),
            'exception_type' => get_class($exception),
        ];

        // Add retry information for retryable errors
        if ($exception->isRetryable()) {
            $context = $exception->getContext();
            $responseData['retry'] = [
                'retryable' => true,
                'retry_after' => $context['retry_after'] ?? 30,
                'max_retries' => $context['max_retries'] ?? null,
            ];
        }

        // Add action guidance for specific error types
        if (isset($exception->getContext()['action'])) {
            $responseData['suggested_action'] = $exception->getContext()['action'];
        }

        // Add debug information in non-production environments
        if (config('app.debug', false)) {
            $responseData['debug'] = [
                'error_description' => $exception->getErrorDescription(),
                'exception_type' => get_class($exception),
                'context' => $exception->getContext(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ];
        }

        $content = json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return new Response($content, $statusCode, [
            'Content-Type' => 'application/json',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    public function createSuccessResponse(array $data = [], string $message = 'OAuth2 operation completed successfully'): Response
    {
        $responseData = [
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ];

        $content = json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return new Response($content, 200, [
            'Content-Type' => 'application/json',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    public function shouldRetry(OAuth2Exception $exception, int $attemptCount = 1): bool
    {
        if (! $exception->isRetryable()) {
            return false;
        }

        $context = $exception->getContext();
        $maxRetries = $context['max_retries'] ?? 3;

        return $attemptCount <= $maxRetries;
    }

    public function getRetryDelay(OAuth2Exception $exception, int $attemptCount = 1): int
    {
        $context = $exception->getContext();
        $baseDelay = $context['retry_after'] ?? 30;

        // Exponential backoff for multiple attempts
        return $baseDelay * (2 ** ($attemptCount - 1));
    }

    private function logError(OAuth2Exception $exception, string $message, array $additionalContext = []): void
    {
        $logLevel = $this->determineLogLevel($exception);
        $context = array_merge($exception->getLoggingContext(), $additionalContext);

        Log::log($logLevel, $message, $context);
    }

    private function determineLogLevel(OAuth2Exception $exception): string
    {
        return match (true) {
            $exception instanceof OAuth2ConfigurationException => 'error',
            $exception instanceof OAuth2TokenExchangeException, $exception instanceof OAuth2TokenRefreshException => 'warning',
            $exception instanceof OAuth2AuthorizationException => match ($exception->getError()) {
                OAuth2Exception::ACCESS_DENIED => 'info',  // User choice, not an error
                OAuth2Exception::SERVER_ERROR, OAuth2Exception::TEMPORARILY_UNAVAILABLE => 'warning',
                default => 'notice',
            },
            default => 'warning',
        };
    }
}
