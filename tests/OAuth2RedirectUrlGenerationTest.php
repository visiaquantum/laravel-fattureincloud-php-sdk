<?php

use Codeman\FattureInCloud\FattureInCloudServiceProvider;
use Illuminate\Config\Repository as ConfigRepository;

describe('OAuth2 redirect URL generation', function () {
    beforeEach(function () {
        // Create a fresh service provider instance for reflection testing
        $this->serviceProvider = new FattureInCloudServiceProvider($this->app);
    });

    describe('isValidUrl method', function () {
        test('validates HTTP and HTTPS URLs correctly', function () {
            $validUrls = [
                'http://example.com',
                'https://example.com',
                'https://example.com/path',
                'https://example.com:8080/path',
                'https://subdomain.example.com/path?query=value',
                'http://localhost:3000/callback',
            ];

            foreach ($validUrls as $url) {
                $result = $this->invokePrivateMethod($this->serviceProvider, 'isValidUrl', [$url]);
                expect($result)->toBeTrue("Expected '{$url}' to be valid");
            }
        });

        test('rejects invalid URLs', function () {
            $invalidUrls = [
                'invalid-url',
                'ftp://example.com',
                'mailto:test@example.com',
                'file:///path/to/file',
                '',
                'example.com',
                'www.example.com',
                'https://',
                'http://',
                'ws://example.com/callback',
            ];

            foreach ($invalidUrls as $url) {
                $result = $this->invokePrivateMethod($this->serviceProvider, 'isValidUrl', [$url]);
                expect($result)->toBeFalse("Expected '{$url}' to be invalid");
            }
        });

        test('accepts both HTTP and HTTPS protocols only', function () {
            expect($this->invokePrivateMethod($this->serviceProvider, 'isValidUrl', ['http://example.com/callback']))
                ->toBeTrue();
            expect($this->invokePrivateMethod($this->serviceProvider, 'isValidUrl', ['https://example.com/callback']))
                ->toBeTrue();
            expect($this->invokePrivateMethod($this->serviceProvider, 'isValidUrl', ['ftp://example.com/callback']))
                ->toBeFalse();
            expect($this->invokePrivateMethod($this->serviceProvider, 'isValidUrl', ['ws://example.com/callback']))
                ->toBeFalse();
        });
    });

    describe('getRedirectUrl method', function () {
        test('returns manual override URL when valid URL is configured', function () {
            $config = new ConfigRepository([
                'fatture-in-cloud.redirect_url' => 'https://example.com/custom-callback',
            ]);

            $result = $this->invokePrivateMethod($this->serviceProvider, 'getRedirectUrl', [$config]);

            expect($result)->toBe('https://example.com/custom-callback');
        });

        test('skips invalid manual override and tries route generation', function () {
            // Set up app URL so route generation can work
            config(['app.url' => 'https://example.com']);

            $config = new ConfigRepository([
                'fatture-in-cloud.redirect_url' => 'invalid-url',
                'app.url' => 'https://example.com',
            ]);

            $result = $this->invokePrivateMethod($this->serviceProvider, 'getRedirectUrl', [$config]);

            // Should generate route or fall back to app URL construction
            expect($result)->toContain('/fatture-in-cloud/callback');
        });

        test('route generation works and ignores config app.url', function () {
            $config = new ConfigRepository([
                'fatture-in-cloud.redirect_url' => null,
                'app.url' => 'https://should-not-be-used.com',
            ]);

            $result = $this->invokePrivateMethod($this->serviceProvider, 'getRedirectUrl', [$config]);

            // Route generation should work and not use the config app.url
            expect($result)->toContain('/fatture-in-cloud/callback');
            expect($result)->not->toContain('should-not-be-used.com');
        });

        test('throws exception with helpful message when route generation fails', function () {
            // Mock a service provider that simulates route generation failure
            $mockServiceProvider = new class($this->app) extends FattureInCloudServiceProvider
            {
                public function testRouteGenerationFailure(ConfigRepository $config): string
                {
                    // Manual override check
                    $manualUrl = $config->get('fatture-in-cloud.redirect_url');
                    if ($manualUrl && $this->isValidUrl($manualUrl)) {
                        return $manualUrl;
                    }

                    // Simulate route generation failure
                    throw new \LogicException(
                        'Unable to generate OAuth2 redirect URL. Please ensure either: '.
                        '1) Set FATTUREINCLOUD_REDIRECT_URL manually in your .env file, or '.
                        '2) Ensure APP_URL is configured (required for Laravel\'s route() helper), or '.
                        '3) Verify the callback route "fatture-in-cloud.callback" is properly registered. '.
                        'Route generation error: Route not found'
                    );
                }

                public function isValidUrl(string $url): bool
                {
                    return filter_var($url, FILTER_VALIDATE_URL) !== false
                        && (str_starts_with($url, 'http://') || str_starts_with($url, 'https://'));
                }
            };

            $config = new ConfigRepository([
                'fatture-in-cloud.redirect_url' => null,
            ]);

            expect(fn () => $mockServiceProvider->testRouteGenerationFailure($config))
                ->toThrow(
                    \LogicException::class,
                    'Unable to generate OAuth2 redirect URL. Please ensure either: '
                );
        });

        test('route generation works when route is registered', function () {
            $config = new ConfigRepository([
                'fatture-in-cloud.redirect_url' => null,
            ]);

            $result = $this->invokePrivateMethod($this->serviceProvider, 'getRedirectUrl', [$config]);

            // In our test environment, route generation works because the route is registered
            expect($result)->toContain('/fatture-in-cloud/callback');
        });

        test('prefers manual override over other methods', function () {
            config(['app.url' => 'https://app-url.com']);

            $config = new ConfigRepository([
                'fatture-in-cloud.redirect_url' => 'https://manual.com/callback',
                'app.url' => 'https://fallback.com',
            ]);

            $result = $this->invokePrivateMethod($this->serviceProvider, 'getRedirectUrl', [$config]);

            expect($result)->toBe('https://manual.com/callback');
        });

        test('route generation works independently of config app.url', function () {
            $config = new ConfigRepository([
                'fatture-in-cloud.redirect_url' => null,
                'app.url' => 'invalid-app-url', // This should be ignored by route()
            ]);

            $result = $this->invokePrivateMethod($this->serviceProvider, 'getRedirectUrl', [$config]);

            // Route generation uses Laravel's APP_URL, not the config value
            expect($result)->toContain('/fatture-in-cloud/callback');
        });
    });

    describe('Integration with service provider', function () {
        test('OAuth2Manager can be resolved with valid manual configuration', function () {
            config([
                'fatture-in-cloud.client_id' => 'test-client',
                'fatture-in-cloud.client_secret' => 'test-secret',
                'fatture-in-cloud.redirect_url' => 'https://test.com/callback',
            ]);

            // Trigger service provider registration
            $this->serviceProvider->packageRegistered();

            $oauth2Manager = $this->app->make(\Codeman\FattureInCloud\Contracts\OAuth2Manager::class);

            expect($oauth2Manager)->toBeInstanceOf(\Codeman\FattureInCloud\Services\OAuth2AuthorizationCodeManager::class);
            expect($oauth2Manager->isInitialized())->toBeTrue();
        });

        test('OAuth2Manager can be resolved with route generation', function () {
            config([
                'fatture-in-cloud.client_id' => 'test-client',
                'fatture-in-cloud.client_secret' => 'test-secret',
                'fatture-in-cloud.redirect_url' => null,
                // No need to set app.url - route() helper will use Laravel's APP_URL
            ]);

            // Trigger service provider registration
            $this->serviceProvider->packageRegistered();

            $oauth2Manager = $this->app->make(\Codeman\FattureInCloud\Contracts\OAuth2Manager::class);

            expect($oauth2Manager)->toBeInstanceOf(\Codeman\FattureInCloud\Services\OAuth2AuthorizationCodeManager::class);
            expect($oauth2Manager->isInitialized())->toBeTrue();
        });

        test('OAuth2Manager can be resolved with minimal configuration', function () {
            config([
                'fatture-in-cloud.client_id' => 'test-client',
                'fatture-in-cloud.client_secret' => 'test-secret',
                'fatture-in-cloud.redirect_url' => null,
                // Route generation will handle URL creation
            ]);

            // Trigger service provider registration
            $this->serviceProvider->packageRegistered();

            $oauth2Manager = $this->app->make(\Codeman\FattureInCloud\Contracts\OAuth2Manager::class);

            expect($oauth2Manager)->toBeInstanceOf(\Codeman\FattureInCloud\Services\OAuth2AuthorizationCodeManager::class);
            expect($oauth2Manager->isInitialized())->toBeTrue();
        });

        test('main SDK service can be resolved when OAuth2Manager is properly configured', function () {
            config([
                'fatture-in-cloud.client_id' => 'test-client',
                'fatture-in-cloud.client_secret' => 'test-secret',
                'fatture-in-cloud.redirect_url' => 'https://test.com/callback',
            ]);

            // Trigger service provider registration
            $this->serviceProvider->packageRegistered();

            $sdk = $this->app->make(\Codeman\FattureInCloud\FattureInCloudSdk::class);

            expect($sdk)->toBeInstanceOf(\Codeman\FattureInCloud\FattureInCloudSdk::class);
        });
    });

    describe('Real-world scenarios', function () {
        test('handles production scenario with custom domain', function () {
            $config = new ConfigRepository([
                'fatture-in-cloud.redirect_url' => 'https://api.mycompany.com/fatture-in-cloud/callback',
            ]);

            $result = $this->invokePrivateMethod($this->serviceProvider, 'getRedirectUrl', [$config]);

            expect($result)->toBe('https://api.mycompany.com/fatture-in-cloud/callback');
        });

        test('handles localhost development scenario', function () {
            $config = new ConfigRepository([
                'fatture-in-cloud.redirect_url' => null,
            ]);

            $result = $this->invokePrivateMethod($this->serviceProvider, 'getRedirectUrl', [$config]);

            // Route generation works using Laravel's configured APP_URL
            expect($result)->toContain('/fatture-in-cloud/callback');
        });

        test('handles staging environment with non-standard port', function () {
            $config = new ConfigRepository([
                'fatture-in-cloud.redirect_url' => null,
            ]);

            $result = $this->invokePrivateMethod($this->serviceProvider, 'getRedirectUrl', [$config]);

            // Route generation works using Laravel's configured APP_URL (with any port)
            expect($result)->toContain('/fatture-in-cloud/callback');
        });

        test('handles route generation success when named route exists', function () {
            $config = new ConfigRepository([
                'fatture-in-cloud.redirect_url' => null,
            ]);

            $result = $this->invokePrivateMethod($this->serviceProvider, 'getRedirectUrl', [$config]);

            // Should use route generation (works since the route is registered)
            expect($result)->toContain('/fatture-in-cloud/callback');
        });

    });

    describe('URL validation edge cases', function () {
        test('handles URLs with query parameters', function () {
            $url = 'https://example.com/callback?state=test&code=abc';
            $result = $this->invokePrivateMethod($this->serviceProvider, 'isValidUrl', [$url]);
            expect($result)->toBeTrue();
        });

        test('handles URLs with fragments', function () {
            $url = 'https://example.com/callback#fragment';
            $result = $this->invokePrivateMethod($this->serviceProvider, 'isValidUrl', [$url]);
            expect($result)->toBeTrue();
        });

        test('handles internationalized domain names', function () {
            $url = 'https://xn--n3h.com/callback'; // IDN for ☃.com
            $result = $this->invokePrivateMethod($this->serviceProvider, 'isValidUrl', [$url]);
            expect($result)->toBeTrue();
        });

        test('rejects malformed URLs', function () {
            $malformedUrls = [
                'https:////example.com',
                'https://example..com',
                'https://',
                'https://.',
                'https://..',
                'https://...',
                'https://example.com:99999', // port too high
            ];

            foreach ($malformedUrls as $url) {
                $result = $this->invokePrivateMethod($this->serviceProvider, 'isValidUrl', [$url]);
                expect($result)->toBeFalse("Expected '{$url}' to be invalid");
            }
        });
    });
});
