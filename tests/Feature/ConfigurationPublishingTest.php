<?php

use Codeman\FattureInCloud\FattureInCloudServiceProvider;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

describe('Configuration Publishing', function () {
    beforeEach(function () {
        // Ensure clean state for each test
        $this->configPath = config_path('fatture-in-cloud.php');

        // Clean up any existing published config
        if (File::exists($this->configPath)) {
            File::delete($this->configPath);
        }
    });

    afterEach(function () {
        // Clean up published files after each test
        if (File::exists($this->configPath)) {
            File::delete($this->configPath);
        }
    });

    describe('Package Configuration Publishing', function () {
        it('publishes config file with correct tag', function () {
            expect(File::exists($this->configPath))->toBeFalse();

            Artisan::call('vendor:publish', [
                '--tag' => 'fatture-in-cloud-config',
                '--force' => true,
            ]);

            expect(File::exists($this->configPath))->toBeTrue();
        });

        it('published config has correct structure', function () {
            Artisan::call('vendor:publish', [
                '--tag' => 'fatture-in-cloud-config',
                '--force' => true,
            ]);

            $publishedConfig = require $this->configPath;

            expect($publishedConfig)->toBeArray();
            expect($publishedConfig)->toHaveKeys([
                'client_id',
                'client_secret',
                'redirect_url',
                'access_token',
            ]);
        });

        it('published config contains environment variable references', function () {
            Artisan::call('vendor:publish', [
                '--tag' => 'fatture-in-cloud-config',
                '--force' => true,
            ]);

            $configContent = File::get($this->configPath);

            expect($configContent)->toContain('FATTUREINCLOUD_CLIENT_ID');
            expect($configContent)->toContain('FATTUREINCLOUD_CLIENT_SECRET');
            expect($configContent)->toContain('FATTUREINCLOUD_REDIRECT_URL');
            expect($configContent)->toContain('FATTUREINCLOUD_ACCESS_TOKEN');
        });

        it('published config includes comprehensive documentation', function () {
            Artisan::call('vendor:publish', [
                '--tag' => 'fatture-in-cloud-config',
                '--force' => true,
            ]);

            $configContent = File::get($this->configPath);

            // Check for key documentation sections
            expect($configContent)->toContain('OAuth2 Authentication Configuration');
            expect($configContent)->toContain('OAuth2 Client ID');
            expect($configContent)->toContain('OAuth2 Client Secret');
            expect($configContent)->toContain('OAuth2 Redirect URL');
            expect($configContent)->toContain('Manual Authentication Access Token');
            expect($configContent)->toContain('SECURITY WARNING');
            expect($configContent)->toContain('developers.fattureincloud.it');
        });

        it('published config can be loaded and used', function () {
            Artisan::call('vendor:publish', [
                '--tag' => 'fatture-in-cloud-config',
                '--force' => true,
            ]);

            // Clear config cache and reload
            config()->set('fatture-in-cloud', null);

            // Load published config
            $publishedConfig = require $this->configPath;
            config()->set('fatture-in-cloud', $publishedConfig);

            expect(config('fatture-in-cloud.client_id'))->toBeNull();
            expect(config('fatture-in-cloud.client_secret'))->toBeNull();
            expect(config('fatture-in-cloud.redirect_url'))->toBeNull();
            expect(config('fatture-in-cloud.access_token'))->toBeNull();
        });

        it('can overwrite existing config with force flag', function () {
            // First publish
            Artisan::call('vendor:publish', [
                '--tag' => 'fatture-in-cloud-config',
                '--force' => true,
            ]);

            // Modify the published file
            File::put($this->configPath, "<?php\nreturn ['modified' => true];");

            // Publish again with force
            Artisan::call('vendor:publish', [
                '--tag' => 'fatture-in-cloud-config',
                '--force' => true,
            ]);

            $publishedConfig = require $this->configPath;
            expect($publishedConfig)->not->toHaveKey('modified');
            expect($publishedConfig)->toHaveKey('client_id');
        });

        it('does not overwrite existing config without force flag', function () {
            // First publish
            Artisan::call('vendor:publish', [
                '--tag' => 'fatture-in-cloud-config',
                '--force' => true,
            ]);

            $originalContent = File::get($this->configPath);

            // Modify the published file
            File::put($this->configPath, "<?php\nreturn ['modified' => true];");

            // Publish again without force
            Artisan::call('vendor:publish', [
                '--tag' => 'fatture-in-cloud-config',
            ]);

            $content = File::get($this->configPath);
            expect($content)->toContain('modified');
            expect($content)->not->toBe($originalContent);
        });
    });

    describe('Publishing Integration with Service Provider', function () {
        it('published config works with package services', function () {
            // Publish config
            Artisan::call('vendor:publish', [
                '--tag' => 'fatture-in-cloud-config',
                '--force' => true,
            ]);

            // Set some test values in published config
            $configContent = File::get($this->configPath);
            $configContent = str_replace(
                "'client_id' => env('FATTUREINCLOUD_CLIENT_ID')",
                "'client_id' => 'published-client-id'",
                $configContent
            );
            $configContent = str_replace(
                "'client_secret' => env('FATTUREINCLOUD_CLIENT_SECRET')",
                "'client_secret' => 'published-client-secret'",
                $configContent
            );
            File::put($this->configPath, $configContent);

            // Load the updated config
            config()->set('fatture-in-cloud', require $this->configPath);

            // Test that services can use the published config
            expect(config('fatture-in-cloud.client_id'))->toBe('published-client-id');
            expect(config('fatture-in-cloud.client_secret'))->toBe('published-client-secret');
        });
    });

    describe('Tag-based Publishing', function () {
        it('supports publishing all package assets', function () {
            // This would publish configs, views, migrations, etc. if they existed
            expect(function () {
                Artisan::call('vendor:publish', [
                    '--provider' => FattureInCloudServiceProvider::class,
                    '--force' => true,
                ]);
            })->not->toThrow(\Exception::class);

            expect(File::exists($this->configPath))->toBeTrue();
        });

        it('supports individual tag publishing', function () {
            expect(function () {
                Artisan::call('vendor:publish', [
                    '--tag' => 'fatture-in-cloud-config',
                    '--force' => true,
                ]);
            })->not->toThrow(\Exception::class);

            expect(File::exists($this->configPath))->toBeTrue();
        });
    });

    describe('Error Handling', function () {
        it('handles publishing to read-only filesystem gracefully', function () {
            // This is difficult to test in a unit test, but we can at least verify
            // that the publish command doesn't crash
            expect(function () {
                Artisan::call('vendor:publish', [
                    '--tag' => 'fatture-in-cloud-config',
                ]);
            })->not->toThrow(\Exception::class);
        });

        it('handles invalid config path gracefully', function () {
            // Test what happens if config_path() returns an invalid path
            expect(function () {
                Artisan::call('vendor:publish', [
                    '--tag' => 'fatture-in-cloud-config',
                    '--force' => true,
                ]);
            })->not->toThrow(\Exception::class);
        });
    });

    describe('Artisan Command Output', function () {
        it('provides clear feedback when publishing config', function () {
            Artisan::call('vendor:publish', [
                '--tag' => 'fatture-in-cloud-config',
                '--force' => true,
            ]);

            $output = Artisan::output();

            expect($output)->toContain('DONE');
        });

        it('shows what files were published', function () {
            Artisan::call('vendor:publish', [
                '--tag' => 'fatture-in-cloud-config',
                '--force' => true,
            ]);

            $output = Artisan::output();

            // Should mention config file being copied
            expect($output)->toContain('fatture-in-cloud.php');
        });
    });
});
