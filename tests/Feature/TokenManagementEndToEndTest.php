<?php

use Codeman\FattureInCloud\Contracts\TokenStorage as TokenStorageContract;
use Codeman\FattureInCloud\Facades\FattureInCloud;
use Codeman\FattureInCloud\FattureInCloudSdk;
use FattureInCloud\OAuth2\OAuth2TokenResponse;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Encryption\Encrypter;

describe('Token Management End-to-End', function () {
    beforeEach(function () {
        // Set up clean test environment
        $this->tokenStorage = app(TokenStorageContract::class);
        $this->sdk = app(FattureInCloudSdk::class);
        $this->cacheRepository = app(CacheRepository::class);
        $this->encrypter = app(Encrypter::class);

        // Clear any existing tokens
        $this->tokenStorage->clear('default');
        $this->tokenStorage->clear('company-123');

        // Set up OAuth2 configuration
        config()->set('fatture-in-cloud.client_id', 'test-client-id');
        config()->set('fatture-in-cloud.client_secret', 'test-client-secret');
        config()->set('fatture-in-cloud.access_token', null); // Use OAuth2 flow
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('Token Storage and Retrieval', function () {
        it('stores and retrieves OAuth2 tokens correctly', function () {
            $accessToken = 'test-access-token-123';
            $refreshToken = 'test-refresh-token-123';
            $expiresIn = 3600;

            // Create OAuth2TokenResponse object
            $tokenResponse = new OAuth2TokenResponse('Bearer', $accessToken, $refreshToken, $expiresIn);

            // Store tokens using correct interface
            $this->tokenStorage->store('default', $tokenResponse);

            // Retrieve tokens using correct interface
            expect($this->tokenStorage->getAccessToken('default'))->toBe($accessToken);
            expect($this->tokenStorage->getRefreshToken('default'))->toBe($refreshToken);

            // Verify token data structure
            $retrievedTokens = $this->tokenStorage->retrieve('default');
            expect($retrievedTokens)->not->toBeNull();
            expect($retrievedTokens)->toHaveKey('access_token', $accessToken);
            expect($retrievedTokens)->toHaveKey('refresh_token', $refreshToken);
            expect($retrievedTokens)->toHaveKey('expires_at');
        });

        it('handles multiple token contexts independently', function () {
            // Create token responses for different contexts
            $token1 = new OAuth2TokenResponse('Bearer', 'token1', 'refresh1', 3600);
            $token2 = new OAuth2TokenResponse('Bearer', 'token2', 'refresh2', 7200);

            // Store tokens for different contexts
            $this->tokenStorage->store('context1', $token1);
            $this->tokenStorage->store('context2', $token2);

            // Verify independence
            expect($this->tokenStorage->getAccessToken('context1'))->toBe('token1');
            expect($this->tokenStorage->getAccessToken('context2'))->toBe('token2');
            expect($this->tokenStorage->getRefreshToken('context1'))->toBe('refresh1');
            expect($this->tokenStorage->getRefreshToken('context2'))->toBe('refresh2');

            // Clear one context shouldn't affect the other
            $this->tokenStorage->clear('context1');
            expect($this->tokenStorage->getAccessToken('context1'))->toBeNull();
            expect($this->tokenStorage->getAccessToken('context2'))->toBe('token2');
        });

        it('encrypts tokens in storage', function () {
            $accessToken = 'sensitive-access-token';
            $refreshToken = 'sensitive-refresh-token';

            $tokenResponse = new OAuth2TokenResponse('Bearer', $accessToken, $refreshToken, 3600);

            $this->tokenStorage->store('default', $tokenResponse);

            // Check that tokens are encrypted in cache
            $rawCacheData = $this->cacheRepository->get('fatture_in_cloud_tokens_default');

            // Raw value should be encrypted (different from original)
            expect($rawCacheData)->not->toBe($accessToken);
            expect($rawCacheData)->not->toBe($refreshToken);
            expect($rawCacheData)->not->toBeNull();

            // But decryption should work through TokenStorage
            expect($this->tokenStorage->getAccessToken('default'))->toBe($accessToken);
            expect($this->tokenStorage->getRefreshToken('default'))->toBe($refreshToken);
        });

        it('handles token expiration correctly', function () {
            // Store token with short expiry (will be expired by the time we check)
            $expiredToken = new OAuth2TokenResponse('Bearer', 'expired-token', 'refresh-token', -1);

            $this->tokenStorage->store('default', $expiredToken);
            expect($this->tokenStorage->isExpired('default'))->toBeTrue();

            // Store valid token with long expiry
            $validToken = new OAuth2TokenResponse('Bearer', 'valid-token', 'refresh-token', 3600);

            $this->tokenStorage->store('default', $validToken);
            expect($this->tokenStorage->isExpired('default'))->toBeFalse();
        });

        it('clears tokens completely', function () {
            $tokenResponse = new OAuth2TokenResponse('Bearer', 'token', 'refresh', 3600);

            // Store token
            $this->tokenStorage->store('default', $tokenResponse);
            expect($this->tokenStorage->getAccessToken('default'))->toBe('token');

            // Clear tokens
            $this->tokenStorage->clear('default');

            expect($this->tokenStorage->getAccessToken('default'))->toBeNull();
            expect($this->tokenStorage->getRefreshToken('default'))->toBeNull();
            expect($this->tokenStorage->retrieve('default'))->toBeNull();
        });

        it('handles non-existent tokens gracefully', function () {
            expect($this->tokenStorage->getAccessToken('non-existent'))->toBeNull();
            expect($this->tokenStorage->getRefreshToken('non-existent'))->toBeNull();
            expect($this->tokenStorage->retrieve('non-existent'))->toBeNull();
            expect($this->tokenStorage->isExpired('non-existent'))->toBeTrue();
        });
    });

    describe('Token Storage Edge Cases', function () {
        it('handles corrupted cache data gracefully', function () {
            // Manually store corrupted data in cache
            $this->cacheRepository->put('fatture_in_cloud_tokens_default', 'corrupted-data');

            // Token storage should handle gracefully
            expect($this->tokenStorage->getAccessToken('default'))->toBeNull();
            expect($this->tokenStorage->getRefreshToken('default'))->toBeNull();
            expect($this->tokenStorage->retrieve('default'))->toBeNull();
        });

        it('handles decryption failure gracefully', function () {
            // Store a token normally first
            $tokenResponse = new OAuth2TokenResponse('Bearer', 'test-token', 'refresh-token', 3600);

            $this->tokenStorage->store('default', $tokenResponse);
            expect($this->tokenStorage->getAccessToken('default'))->toBe('test-token');

            // Manually corrupt the encrypted data to simulate decryption failure
            $this->cacheRepository->put('fatture_in_cloud_tokens_default', 'invalid-encrypted-data');

            // Should handle decryption failure gracefully and clear the corrupted data
            expect($this->tokenStorage->getAccessToken('default'))->toBeNull();
        });

        it('handles concurrent token access', function () {
            $tokenResponse = new OAuth2TokenResponse('Bearer', 'concurrent-token', 'refresh', 3600);

            // Simulate concurrent access by multiple processes
            $this->tokenStorage->store('default', $tokenResponse);

            // Multiple simultaneous reads should be safe
            $results = [];
            for ($i = 0; $i < 10; $i++) {
                $results[] = $this->tokenStorage->getAccessToken('default');
            }

            // All reads should return the same value
            foreach ($results as $result) {
                expect($result)->toBe('concurrent-token');
            }
        });
    });

    describe('SDK Integration', function () {
        it('SDK uses TokenStorage for API authentication', function () {
            // Store a token
            $tokenResponse = new OAuth2TokenResponse('Bearer', 'sdk-test-token', 'sdk-refresh-token', 3600);

            $this->tokenStorage->store('default', $tokenResponse);

            // SDK should use the stored token for API calls
            $companiesApi = $this->sdk->companies();
            expect($companiesApi->getConfig()->getAccessToken())->toBe('sdk-test-token');
        });

        it('SDK respects token expiration', function () {
            // Store expired token
            $expiredToken = new OAuth2TokenResponse('Bearer', 'expired-token', 'refresh-token', -1);

            $this->tokenStorage->store('default', $expiredToken);

            // SDK should detect expiration
            expect($this->sdk->isTokenExpired())->toBeTrue();
        });
    });

    describe('Facade Token Management', function () {
        it('facade delegates to TokenStorage correctly', function () {
            // Store token
            $tokenResponse = new OAuth2TokenResponse('Bearer', 'facade-token', 'facade-refresh', 3600);

            $this->tokenStorage->store('default', $tokenResponse);

            // Check token status via facade
            expect(FattureInCloud::isTokenExpired())->toBeFalse();

            // Clear tokens via facade
            FattureInCloud::clearTokens();

            expect(FattureInCloud::isTokenExpired())->toBeTrue();
        });
    });

    describe('Performance and Efficiency', function () {
        it('token operations are efficient', function () {
            $start = microtime(true);

            // Perform multiple token operations
            for ($i = 0; $i < 10; $i++) {
                $tokenResponse = new OAuth2TokenResponse('Bearer', "token{$i}", "refresh{$i}", 3600);

                $this->tokenStorage->store("context{$i}", $tokenResponse);
                $this->tokenStorage->getAccessToken("context{$i}");
                $this->tokenStorage->isExpired("context{$i}");
            }

            $duration = microtime(true) - $start;

            // Should complete quickly (under 100ms even on slow systems)
            expect($duration)->toBeLessThan(0.1);
        });
    });
});
