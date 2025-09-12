<?php

use Codeman\FattureInCloud\Contracts\ApiServiceFactory as ApiServiceFactoryContract;
use Codeman\FattureInCloud\Facades\FattureInCloud;
use Codeman\FattureInCloud\FattureInCloudSdk;
use FattureInCloud\Api\ArchiveApi;
use FattureInCloud\Api\CashbookApi;
use FattureInCloud\Api\ClientsApi;
use FattureInCloud\Api\CompaniesApi;
use FattureInCloud\Api\InfoApi;
use FattureInCloud\Api\IssuedDocumentsApi;
use FattureInCloud\Api\PriceListsApi;
use FattureInCloud\Api\ProductsApi;
use FattureInCloud\Api\ReceiptsApi;
use FattureInCloud\Api\ReceivedDocumentsApi;
use FattureInCloud\Api\SettingsApi;
use FattureInCloud\Api\SuppliersApi;
use FattureInCloud\Api\TaxesApi;
use FattureInCloud\Api\UserApi;
use FattureInCloud\Configuration;
use FattureInCloud\Model\Client;
use FattureInCloud\Model\GetCompanyInfoResponse;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

describe('API Operations Integration', function () {
    beforeEach(function () {
        // Set up a mock access token for API operations
        config()->set('fatture-in-cloud.access_token', 'test-access-token-12345');

        // Mock HTTP responses container
        $this->httpHistory = [];
        $this->mockResponses = [];
        $this->mockHandler = new MockHandler;
        $this->handlerStack = HandlerStack::create($this->mockHandler);
        $this->handlerStack->push(Middleware::history($this->httpHistory));

        // Create SDK instance for testing
        $this->sdk = app(FattureInCloudSdk::class);
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('API Service Factory Integration', function () {
        it('creates all supported API services', function () {
            $factory = app(ApiServiceFactoryContract::class);

            $services = [
                'clients' => ClientsApi::class,
                'companies' => CompaniesApi::class,
                'info' => InfoApi::class,
                'issuedDocuments' => IssuedDocumentsApi::class,
                'products' => ProductsApi::class,
                'receipts' => ReceiptsApi::class,
                'receivedDocuments' => ReceivedDocumentsApi::class,
                'suppliers' => SuppliersApi::class,
                'taxes' => TaxesApi::class,
                'user' => UserApi::class,
                'settings' => SettingsApi::class,
                'archive' => ArchiveApi::class,
                'cashbook' => CashbookApi::class,
                'priceLists' => PriceListsApi::class,
            ];

            foreach ($services as $serviceName => $expectedClass) {
                $service = $factory->make($serviceName);
                expect($service)->toBeInstanceOf($expectedClass);
                expect($service->getConfig()->getAccessToken())->toBe('test-access-token-12345');
            }
        });

        it('created services share configuration', function () {
            $factory = app(ApiServiceFactoryContract::class);

            $clientsApi = $factory->make('clients');
            $companiesApi = $factory->make('companies');

            expect($clientsApi->getConfig()->getAccessToken())
                ->toBe($companiesApi->getConfig()->getAccessToken());

            expect($clientsApi->getConfig()->getHost())
                ->toBe($companiesApi->getConfig()->getHost());
        });

        it('creates fresh instances for each call', function () {
            $factory = app(ApiServiceFactoryContract::class);

            $clientsApi1 = $factory->make('clients');
            $clientsApi2 = $factory->make('clients');

            expect($clientsApi1)->not->toBe($clientsApi2);
        });
    });

    describe('SDK API Method Integration', function () {
        it('provides direct access to all API services', function () {
            $sdk = app(FattureInCloudSdk::class);

            expect($sdk->clients())->toBeInstanceOf(ClientsApi::class);
            expect($sdk->companies())->toBeInstanceOf(CompaniesApi::class);
            expect($sdk->info())->toBeInstanceOf(InfoApi::class);
            expect($sdk->issuedDocuments())->toBeInstanceOf(IssuedDocumentsApi::class);
            expect($sdk->products())->toBeInstanceOf(ProductsApi::class);
            expect($sdk->receipts())->toBeInstanceOf(ReceiptsApi::class);
            expect($sdk->receivedDocuments())->toBeInstanceOf(ReceivedDocumentsApi::class);
            expect($sdk->suppliers())->toBeInstanceOf(SuppliersApi::class);
            expect($sdk->taxes())->toBeInstanceOf(TaxesApi::class);
            expect($sdk->user())->toBeInstanceOf(UserApi::class);
            expect($sdk->settings())->toBeInstanceOf(SettingsApi::class);
            expect($sdk->archive())->toBeInstanceOf(ArchiveApi::class);
            expect($sdk->cashbook())->toBeInstanceOf(CashbookApi::class);
            expect($sdk->priceLists())->toBeInstanceOf(PriceListsApi::class);
        });

        it('all API services have correct authentication configured', function () {
            $sdk = app(FattureInCloudSdk::class);

            $apis = [
                $sdk->clients(),
                $sdk->companies(),
                $sdk->info(),
                $sdk->issuedDocuments(),
                $sdk->products(),
                $sdk->receipts(),
                $sdk->receivedDocuments(),
                $sdk->suppliers(),
                $sdk->taxes(),
                $sdk->user(),
                $sdk->settings(),
                $sdk->archive(),
                $sdk->cashbook(),
                $sdk->priceLists(),
            ];

            foreach ($apis as $api) {
                expect($api->getConfig()->getAccessToken())->toBe('test-access-token-12345');
                expect($api->getConfig()->getHost())->toBe('https://api-v2.fattureincloud.it');
            }
        });
    });

    describe('Facade API Integration', function () {
        it('provides access to all API services via facade', function () {
            expect(FattureInCloud::clients())->toBeInstanceOf(ClientsApi::class);
            expect(FattureInCloud::companies())->toBeInstanceOf(CompaniesApi::class);
            expect(FattureInCloud::info())->toBeInstanceOf(InfoApi::class);
            expect(FattureInCloud::issuedDocuments())->toBeInstanceOf(IssuedDocumentsApi::class);
            expect(FattureInCloud::products())->toBeInstanceOf(ProductsApi::class);
            expect(FattureInCloud::receipts())->toBeInstanceOf(ReceiptsApi::class);
            expect(FattureInCloud::receivedDocuments())->toBeInstanceOf(ReceivedDocumentsApi::class);
            expect(FattureInCloud::suppliers())->toBeInstanceOf(SuppliersApi::class);
            expect(FattureInCloud::taxes())->toBeInstanceOf(TaxesApi::class);
            expect(FattureInCloud::user())->toBeInstanceOf(UserApi::class);
            expect(FattureInCloud::settings())->toBeInstanceOf(SettingsApi::class);
            expect(FattureInCloud::archive())->toBeInstanceOf(ArchiveApi::class);
            expect(FattureInCloud::cashbook())->toBeInstanceOf(CashbookApi::class);
            expect(FattureInCloud::priceLists())->toBeInstanceOf(PriceListsApi::class);
        });

        it('facade and direct SDK access return equivalent services', function () {
            $sdk = app(FattureInCloudSdk::class);

            // Services from facade and SDK should have same configuration
            $facadeClients = FattureInCloud::clients();
            $sdkClients = $sdk->clients();

            expect($facadeClients->getConfig()->getAccessToken())
                ->toBe($sdkClients->getConfig()->getAccessToken());
        });
    });

    describe('Mocked API Operations', function () {
        beforeEach(function () {
            // We'll mock the HTTP client for API services
            $this->mockHttpClient = Mockery::mock(HttpClient::class);
        });

        it('handles successful API responses', function () {
            // Mock a successful companies list response
            $mockResponse = new Response(200, [], json_encode([
                'data' => [
                    [
                        'id' => 12345,
                        'name' => 'Test Company',
                        'type' => 'company',
                        'access_token' => 'token123',
                    ],
                ],
            ]));

            $mockHandler = new MockHandler([$mockResponse]);
            $handlerStack = HandlerStack::create($mockHandler);
            $mockHttpClient = new HttpClient(['handler' => $handlerStack]);

            // Create API service with mocked HTTP client
            $config = new Configuration;
            $config->setAccessToken('test-token');

            $companiesApi = new CompaniesApi($mockHttpClient, $config);
            $response = $companiesApi->getCompanyInfo(1);

            expect($response)->toBeInstanceOf(GetCompanyInfoResponse::class);
            expect($response->getData())->not->toBeEmpty();
        });

        it('handles API error responses gracefully', function () {
            // Mock an error response
            $mockResponse = new Response(401, [], json_encode([
                'error' => 'unauthorized',
                'error_description' => 'Invalid access token',
            ]));

            $mockHandler = new MockHandler([$mockResponse]);
            $handlerStack = HandlerStack::create($mockHandler);
            $mockHttpClient = new HttpClient(['handler' => $handlerStack]);

            $config = new Configuration;
            $config->setAccessToken('invalid-token');

            $userApi = new UserApi($mockHttpClient, $config);

            expect(function () use ($userApi) {
                $userApi->listUserCompanies();
            })->toThrow(\FattureInCloud\ApiException::class);
        });

        it('sends correct authentication headers', function () {
            $mockResponse = new Response(200, [], json_encode(['data' => []]));

            $history = [];
            $mockHandler = new MockHandler([$mockResponse]);
            $handlerStack = HandlerStack::create($mockHandler);
            $handlerStack->push(Middleware::history($history));
            $mockHttpClient = new HttpClient(['handler' => $handlerStack]);

            $config = new Configuration;
            $config->setAccessToken('test-access-token-12345');

            $companiesApi = new CompaniesApi($mockHttpClient, $config);
            $companiesApi->getCompanyInfo(1);

            expect($history)->toHaveCount(1);
            $request = $history[0]['request'];
            expect($request->getHeaderLine('Authorization'))->toBe('Bearer test-access-token-12345');
        });

        it('makes requests to correct API endpoints', function () {
            $mockResponse = new Response(200, [], json_encode(['data' => []]));

            $history = [];
            $mockHandler = new MockHandler([$mockResponse]);
            $handlerStack = HandlerStack::create($mockHandler);
            $handlerStack->push(Middleware::history($history));
            $mockHttpClient = new HttpClient(['handler' => $handlerStack]);

            $config = new Configuration;
            $config->setAccessToken('test-token');

            $companiesApi = new CompaniesApi($mockHttpClient, $config);
            $companiesApi->getCompanyInfo(1);

            expect($history)->toHaveCount(1);
            $request = $history[0]['request'];
            expect($request->getUri()->getPath())->toContain('/company/info');
            expect($request->getUri()->getHost())->toBe('api-v2.fattureincloud.it');
        });

        it('handles different HTTP methods correctly', function () {
            // Test GET request
            $getResponse = new Response(200, [], json_encode(['data' => []]));

            // Test POST request (we'll mock creating a client)
            $postResponse = new Response(201, [], json_encode([
                'data' => [
                    'id' => 123,
                    'name' => 'Test Client',
                    'code' => 'TC001',
                ],
            ]));

            $history = [];
            $mockHandler = new MockHandler([$getResponse, $postResponse]);
            $handlerStack = HandlerStack::create($mockHandler);
            $handlerStack->push(Middleware::history($history));
            $mockHttpClient = new HttpClient(['handler' => $handlerStack]);

            $config = new Configuration;
            $config->setAccessToken('test-token');

            $clientsApi = new ClientsApi($mockHttpClient, $config);

            // GET request
            $clientsApi->listClients(12345);

            // POST request
            $client = new Client(['name' => 'Test Client', 'code' => 'TC001']);
            $clientsApi->createClient(12345, $client);

            expect($history)->toHaveCount(2);
            expect($history[0]['request']->getMethod())->toBe('GET');
            expect($history[1]['request']->getMethod())->toBe('POST');
        });
    });

    describe('Real-world API Usage Patterns', function () {
        it('supports method chaining for common operations', function () {
            // This tests that you can chain operations (though APIs don't return fluent interfaces)
            expect(function () {
                $sdk = app(FattureInCloudSdk::class);
                $companiesApi = $sdk->companies();
                $clientsApi = $sdk->clients();
                $productsApi = $sdk->products();

                expect($companiesApi)->toBeInstanceOf(CompaniesApi::class);
                expect($clientsApi)->toBeInstanceOf(ClientsApi::class);
                expect($productsApi)->toBeInstanceOf(ProductsApi::class);
            })->not->toThrow(\Exception::class);
        });

        it('maintains consistent configuration across service calls', function () {
            $sdk = app(FattureInCloudSdk::class);

            // All services should share the same base configuration
            $companiesConfig = $sdk->companies()->getConfig();
            $clientsConfig = $sdk->clients()->getConfig();
            $productsConfig = $sdk->products()->getConfig();

            expect($companiesConfig->getAccessToken())->toBe($clientsConfig->getAccessToken());
            expect($clientsConfig->getAccessToken())->toBe($productsConfig->getAccessToken());
            expect($companiesConfig->getHost())->toBe($clientsConfig->getHost());
            expect($clientsConfig->getHost())->toBe($productsConfig->getHost());
        });

        it('handles service instantiation efficiently', function () {
            $sdk = app(FattureInCloudSdk::class);

            // Multiple calls to same service should be efficient
            $start = microtime(true);

            for ($i = 0; $i < 10; $i++) {
                $api = $sdk->clients();
                expect($api)->toBeInstanceOf(ClientsApi::class);
            }

            $duration = microtime(true) - $start;

            // Should complete quickly (under 100ms even on slow systems)
            expect($duration)->toBeLessThan(0.1);
        });

        it('provides access to underlying SDK configuration', function () {
            $sdk = app(FattureInCloudSdk::class);

            $companiesApi = $sdk->companies();
            $config = $companiesApi->getConfig();

            expect($config)->toBeInstanceOf(Configuration::class);
            expect($config->getAccessToken())->toBe('test-access-token-12345');
            expect($config->getHost())->toBe('https://api-v2.fattureincloud.it');
            expect($config->getUserAgent())->toContain('FattureInCloud');
        });
    });

    describe('Error Handling and Edge Cases', function () {
        it('handles missing authentication gracefully', function () {
            config()->set('fatture-in-cloud.access_token', null);
            config()->set('fatture-in-cloud.client_id', null);
            config()->set('fatture-in-cloud.client_secret', null);

            // Clear the container to ensure fresh instances
            app()->forgetInstance(FattureInCloudSdk::class);
            app()->forgetInstance(ApiServiceFactoryContract::class);
            app()->forgetInstance('Codeman\FattureInCloud\Contracts\ApiServiceFactory');

            // Services should still be created but without authentication
            $sdk = app(FattureInCloudSdk::class);
            $companiesApi = $sdk->companies();

            expect($companiesApi)->toBeInstanceOf(CompaniesApi::class);
            expect($companiesApi->getConfig()->getAccessToken())->toBeEmpty();
        });

        it('handles concurrent service access', function () {
            $sdk = app(FattureInCloudSdk::class);

            // Simulate concurrent access to different services
            $services = [];
            $services[] = $sdk->clients();
            $services[] = $sdk->companies();
            $services[] = $sdk->products();
            $services[] = $sdk->suppliers();

            expect($services)->toHaveCount(4);

            foreach ($services as $service) {
                expect($service->getConfig()->getAccessToken())->toBe('test-access-token-12345');
            }
        });
    });
});
