<?php

namespace Codeman\LaravelFattureInCloudPhpSdk\Contracts;

use FattureInCloud\OAuth2\OAuth2TokenResponse;

interface OAuth2ManagerInterface
{
    public function getAuthorizationUrl(array $scopes, ?string $state = null): string;

    public function fetchToken(string $code, string $state): OAuth2TokenResponse;

    public function refreshToken(string $refreshToken): ?OAuth2TokenResponse;

    public function isInitialized(): bool;
}
