<?php

namespace Codeman\FattureInCloud\Tests;

use Codeman\FattureInCloud\FattureInCloudServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Codeman\\FattureInCloud\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            FattureInCloudServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        // Set encryption key for testing
        config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        // Configure cache for TokenStorage
        config()->set('cache.default', 'array');

        // Configure session for StateManager
        config()->set('session.driver', 'array');

        // Configure the package with the corrected config keys
        config()->set('fatture-in-cloud.client_id', 'test-client-id');
        config()->set('fatture-in-cloud.client_secret', 'test-client-secret');
        config()->set('fatture-in-cloud.redirect_url', 'http://localhost/fatture-in-cloud/callback');

        /*
         foreach (\Illuminate\Support\Facades\File::allFiles(__DIR__ . '/database/migrations') as $migration) {
            (include $migration->getRealPath())->up();
         }
         */
    }

    /**
     * Helper method to invoke private methods for testing
     */
    protected function invokePrivateMethod($object, $methodName, $parameters = [])
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
