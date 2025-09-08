<?php

namespace Codeman\FattureInCloud\Services;

use Codeman\FattureInCloud\Contracts\OAuth2Manager as OAuth2ManagerContract;
use Codeman\FattureInCloud\Contracts\StateManager as StateManagerContract;
use Codeman\FattureInCloud\Exceptions\OAuth2Exception;
use FattureInCloud\OAuth2\OAuth2AuthorizationCode\OAuth2AuthorizationCodeManager as FattureInCloudOAuth2Manager;
use FattureInCloud\OAuth2\OAuth2Error;
use FattureInCloud\OAuth2\OAuth2TokenResponse;
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
            throw OAuth2Exception::fromOAuth2Error($tokenResponse);
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
            throw OAuth2Exception::fromOAuth2Error($tokenResponse);
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

        $this->oauthManager = new FattureInCloudOAuth2Manager(
            $this->clientId,
            $this->clientSecret,
            $this->redirectUrl
        );
    }
}
