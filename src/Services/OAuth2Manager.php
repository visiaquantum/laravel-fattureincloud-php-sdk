<?php

namespace Codeman\LaravelFattureInCloudPhpSdk\Services;

use Codeman\LaravelFattureInCloudPhpSdk\Contracts\OAuth2ManagerInterface;
use Codeman\LaravelFattureInCloudPhpSdk\Contracts\StateManagerInterface;
use FattureInCloud\OAuth2\OAuth2AuthorizationCode\OAuth2AuthorizationCodeManager;
use FattureInCloud\OAuth2\OAuth2Error;
use FattureInCloud\OAuth2\OAuth2TokenResponse;
use Illuminate\Support\Str;

class OAuth2Manager implements OAuth2ManagerInterface
{
    private ?OAuth2AuthorizationCodeManager $oauthManager = null;

    public function __construct(
        private StateManagerInterface $stateManager,
        private ?string $clientId = null,
        private ?string $clientSecret = null,
        private ?string $redirectUrl = null
    ) {
        $this->initializeIfReady();
    }

    public function getAuthorizationUrl(array $scopes, ?string $state = null): string
    {
        if (! $this->isInitialized()) {
            throw new \LogicException('OAuth2 manager not initialized. Cannot generate authorization URL.');
        }

        if (! $state) {
            $state = Str::random(40);
        }

        $this->stateManager->store($state);

        return $this->oauthManager->getAuthorizationUrl($scopes, $state);
    }

    public function fetchToken(string $code, string $state): OAuth2TokenResponse
    {
        if (! $this->isInitialized()) {
            throw new \LogicException('OAuth2 manager not initialized. Cannot fetch token.');
        }

        if (! $this->stateManager->validate($state)) {
            throw new \InvalidArgumentException('Invalid state parameter. Possible CSRF attack.');
        }

        $tokenResponse = $this->oauthManager->fetchToken($code);

        if ($tokenResponse instanceof OAuth2Error) {
            throw new \RuntimeException('OAuth2 error: '.$tokenResponse->getError().' - '.$tokenResponse->getErrorDescription());
        }

        $this->stateManager->clear();

        return $tokenResponse;
    }

    public function refreshToken(string $refreshToken): ?OAuth2TokenResponse
    {
        if (! $this->isInitialized()) {
            throw new \LogicException('OAuth2 manager not initialized. Cannot refresh token.');
        }

        $tokenResponse = $this->oauthManager->refreshToken($refreshToken);

        if ($tokenResponse instanceof OAuth2Error) {
            throw new \RuntimeException('OAuth2 refresh error: '.$tokenResponse->getError().' - '.$tokenResponse->getErrorDescription());
        }

        return $tokenResponse;
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

        $this->oauthManager = new OAuth2AuthorizationCodeManager(
            $this->clientId,
            $this->clientSecret,
            $this->redirectUrl
        );
    }
}
