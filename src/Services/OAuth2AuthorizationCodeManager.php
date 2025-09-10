<?php

namespace Codeman\FattureInCloud\Services;

use Codeman\FattureInCloud\Contracts\OAuth2Manager as OAuth2ManagerContract;
use Codeman\FattureInCloud\Contracts\StateManager as StateManagerContract;
use Codeman\FattureInCloud\Exceptions\OAuth2Exception;
use FattureInCloud\OAuth2\OAuth2AuthorizationCode\OAuth2AuthorizationCodeManager as FattureInCloudOAuth2Manager;
use FattureInCloud\OAuth2\OAuth2Error;
use FattureInCloud\OAuth2\OAuth2TokenResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OAuth2AuthorizationCodeManager implements OAuth2ManagerContract
{
    private ?FattureInCloudOAuth2Manager $oauthManager = null;

    public function __construct(
        private StateManagerContract $stateManager,
        private ?string $clientId = null,
        private ?string $clientSecret = null,
        private ?string $redirectUrl = null
    ) {
        $this->initializeIfReady();
    }

    public function getAuthorizationUrl(array $scopes, ?string $state = null): string
    {
        if (! $this->isInitialized()) {
            throw OAuth2Exception::missingConfiguration(
                'oauth2_credentials',
                'OAuth2 manager not initialized. Check CLIENT_ID, CLIENT_SECRET, and REDIRECT_URL configuration.'
            );
        }

        if (! $state) {
            $state = Str::random(40);
        }

        try {
            $this->stateManager->store($state);
            $authUrl = $this->oauthManager->getAuthorizationUrl($scopes, $state);

            Log::info('OAuth2 authorization URL generated successfully', [
                'scopes' => $scopes,
                'state' => substr($state, 0, 8).'...',  // Log only first 8 chars for security
            ]);

            return $authUrl;
        } catch (\Exception $e) {
            Log::error('Failed to generate OAuth2 authorization URL', [
                'error' => $e->getMessage(),
                'scopes' => $scopes,
            ]);

            throw OAuth2Exception::serverError('Failed to generate authorization URL: '.$e->getMessage());
        }
    }

    public function fetchToken(string $code, string $state): OAuth2TokenResponse
    {
        if (! $this->isInitialized()) {
            throw OAuth2Exception::missingConfiguration(
                'oauth2_credentials',
                'OAuth2 manager not initialized. Cannot fetch token.'
            );
        }

        if (! $this->stateManager->validate($state)) {
            Log::warning('OAuth2 token exchange failed: invalid state parameter', [
                'provided_state' => substr($state, 0, 8).'...',
            ]);

            throw OAuth2Exception::invalidRequest('Invalid state parameter. Possible CSRF attack or session expired.');
        }

        try {
            $tokenResponse = $this->oauthManager->fetchToken($code);

            if ($tokenResponse instanceof OAuth2Error) {
                Log::warning('OAuth2 token exchange returned error', [
                    'error' => $tokenResponse->getError(),
                    'error_description' => $tokenResponse->getErrorDescription(),
                ]);

                throw OAuth2Exception::fromOAuth2Error($tokenResponse);
            }

            $this->stateManager->clear();

            Log::info('OAuth2 token exchange completed successfully', [
                'token_type' => $tokenResponse->getTokenType(),
                'expires_in' => $tokenResponse->getExpiresIn(),
                'has_refresh_token' => ! empty($tokenResponse->getRefreshToken()),
            ]);

            return $tokenResponse;
        } catch (OAuth2Exception $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('OAuth2 token exchange failed with unexpected error', [
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
            ]);

            // Check if it's a network-related error
            if ($this->isNetworkError($e)) {
                throw OAuth2Exception::networkFailure('Network error during token exchange', $e);
            }

            throw OAuth2Exception::invalidCode('Token exchange failed: '.$e->getMessage());
        }
    }

    public function refreshToken(string $refreshToken): ?OAuth2TokenResponse
    {
        if (! $this->isInitialized()) {
            throw OAuth2Exception::missingConfiguration(
                'oauth2_credentials',
                'OAuth2 manager not initialized. Cannot refresh token.'
            );
        }

        try {
            $tokenResponse = $this->oauthManager->refreshToken($refreshToken);

            if ($tokenResponse instanceof OAuth2Error) {
                Log::warning('OAuth2 token refresh returned error', [
                    'error' => $tokenResponse->getError(),
                    'error_description' => $tokenResponse->getErrorDescription(),
                ]);

                // Map OAuth2Error to specific refresh token exceptions
                throw match ($tokenResponse->getError()) {
                    'invalid_grant' => OAuth2Exception::invalidRefreshToken($tokenResponse->getErrorDescription()),
                    'invalid_client', 'unauthorized_client' => OAuth2Exception::clientAuthenticationFailed($tokenResponse->getErrorDescription()),
                    default => OAuth2Exception::fromOAuth2Error($tokenResponse),
                };
            }

            Log::info('OAuth2 token refresh completed successfully', [
                'token_type' => $tokenResponse->getTokenType(),
                'expires_in' => $tokenResponse->getExpiresIn(),
                'has_refresh_token' => ! empty($tokenResponse->getRefreshToken()),
            ]);

            return $tokenResponse;
        } catch (OAuth2Exception $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('OAuth2 token refresh failed with unexpected error', [
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
            ]);

            // Check if it's a network-related error
            if ($this->isNetworkError($e)) {
                throw OAuth2Exception::networkFailure('Network error during token refresh', $e);
            }

            throw OAuth2Exception::invalidRefreshToken('Token refresh failed: '.$e->getMessage());
        }
    }

    public function isInitialized(): bool
    {
        return $this->oauthManager !== null;
    }

    private function initializeIfReady(): void
    {
        if (! $this->clientId || ! $this->clientSecret || ! $this->redirectUrl) {
            return;
        }

        try {
            $this->oauthManager = new FattureInCloudOAuth2Manager(
                $this->clientId,
                $this->clientSecret,
                $this->redirectUrl
            );
        } catch (\Exception $e) {
            Log::error('Failed to initialize OAuth2 manager', [
                'error' => $e->getMessage(),
                'client_id_provided' => ! empty($this->clientId),
                'client_secret_provided' => ! empty($this->clientSecret),
                'redirect_url' => $this->redirectUrl,
            ]);

            throw OAuth2Exception::malformedConfiguration('Failed to initialize OAuth2 manager: '.$e->getMessage());
        }
    }

    private function isNetworkError(\Exception $e): bool
    {
        // Check for specific network-related exception types (Guzzle is guaranteed to be available)
        if ($e instanceof \GuzzleHttp\Exception\ConnectException ||
            $e instanceof \GuzzleHttp\Exception\RequestException ||
            $e instanceof \GuzzleHttp\Exception\TransferException) {
            return true;
        }

        // Check for network-related error messages
        $networkErrorMessages = [
            'connection timed out',
            'connection refused',
            'network is unreachable',
            'name resolution failed',
            'ssl connection error',
            'could not resolve host',
            'operation timed out',
            'curl error',
            'timeout',
        ];

        $errorMessage = strtolower($e->getMessage());

        foreach ($networkErrorMessages as $networkMessage) {
            if (str_contains($errorMessage, $networkMessage)) {
                return true;
            }
        }

        return false;
    }
}
