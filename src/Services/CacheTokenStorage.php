<?php

namespace Codeman\FattureInCloud\Services;

use Codeman\FattureInCloud\Contracts\TokenStorage as TokenStorageContract;
use FattureInCloud\OAuth2\OAuth2TokenResponse;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Encryption\Encrypter;

class CacheTokenStorage implements TokenStorageContract
{
    public function __construct(
        private CacheRepository $cache,
        private Encrypter $encrypter
    ) {}

    public function store(string $key, OAuth2TokenResponse $token): void
    {
        $encryptedData = $this->encrypter->encrypt([
            'access_token' => $token->getAccessToken(),
            'refresh_token' => $token->getRefreshToken(),
            'expires_at' => now()->addSeconds($token->getExpiresIn())->timestamp,
        ]);

        $this->cache->put($this->getCacheKey($key), $encryptedData, now()->addYear());
    }

    public function storeTokens(string $key, string $accessToken, string $refreshToken, int $expiresAt): void
    {
        $encryptedData = $this->encrypter->encrypt([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_at' => $expiresAt,
        ]);

        $this->cache->put($this->getCacheKey($key), $encryptedData, now()->addYear());
    }

    public function retrieve(string $key): ?array
    {
        $encryptedData = $this->cache->get($this->getCacheKey($key));

        if (! $encryptedData) {
            return null;
        }

        try {
            return $this->encrypter->decrypt($encryptedData);
        } catch (\Exception) {
            $this->clear($key);

            return null;
        }
    }

    public function clear(string $key): void
    {
        $this->cache->forget($this->getCacheKey($key));
    }

    public function isExpired(string $key): bool
    {
        $tokens = $this->retrieve($key);

        if (! $tokens || ! isset($tokens['expires_at'])) {
            return true;
        }

        return now()->timestamp >= $tokens['expires_at'];
    }

    public function getAccessToken(string $key): ?string
    {
        $tokens = $this->retrieve($key);

        return $tokens['access_token'] ?? null;
    }

    public function getRefreshToken(string $key): ?string
    {
        $tokens = $this->retrieve($key);

        return $tokens['refresh_token'] ?? null;
    }

    private function getCacheKey(string $key): string
    {
        return "fatture_in_cloud_tokens_{$key}";
    }
}
