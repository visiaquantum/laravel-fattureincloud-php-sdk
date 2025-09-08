<?php

namespace Codeman\LaravelFattureInCloudPhpSdk\Contracts;

use FattureInCloud\OAuth2\OAuth2TokenResponse;

interface TokenStorage
{
    public function store(string $key, OAuth2TokenResponse $token): void;

    public function retrieve(string $key): ?array;

    public function clear(string $key): void;

    public function isExpired(string $key): bool;

    public function getAccessToken(string $key): ?string;

    public function getRefreshToken(string $key): ?string;
}
