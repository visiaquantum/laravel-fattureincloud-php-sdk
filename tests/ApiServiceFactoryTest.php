<?php

use Codeman\FattureInCloud\Services\FattureInCloudApiServiceFactory;
use Codeman\FattureInCloud\Exceptions\UnsupportedServiceException;
use FattureInCloud\Configuration;
use FattureInCloud\HeaderSelector;
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
use GuzzleHttp\Client as HttpClient;

describe('FattureInCloudApiServiceFactory', function () {
    beforeEach(function () {
        $this->httpClient = Mockery::mock(HttpClient::class);
        $this->configuration = Mockery::mock(Configuration::class);
        $this->headerSelector = Mockery::mock(HeaderSelector::class);
        
        $this->factory = new FattureInCloudApiServiceFactory(
            $this->httpClient,
            $this->configuration,
            $this->headerSelector
        );
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('constructor', function () {
        test('can be instantiated with dependencies', function () {
            expect($this->factory)->toBeInstanceOf(FattureInCloudApiServiceFactory::class);
        });

        test('implements ApiServiceFactory contract', function () {
            expect($this->factory)->toBeInstanceOf(\Codeman\FattureInCloud\Contracts\ApiServiceFactory::class);
        });
    });

    describe('supported services', function () {
        test('getSupportedServices returns all supported service names', function () {
            $supportedServices = $this->factory->getSupportedServices();
            
            $expectedServices = [
                'clients',
                'companies', 
                'info',
                'issuedDocuments',
                'products',
                'receipts',
                'receivedDocuments',
                'suppliers',
                'taxes',
                'user',
                'settings',
                'archive',
                'cashbook',
                'priceLists',
            ];
            
            expect($supportedServices)->toBe($expectedServices);
            expect(count($supportedServices))->toBe(14);
        });

        test('supports returns true for valid service names', function () {
            $validServices = [
                'clients',
                'companies',
                'info',
                'issuedDocuments',
                'products',
                'receipts',
                'receivedDocuments',
                'suppliers',
                'taxes',
                'user',
                'settings',
                'archive',
                'cashbook',
                'priceLists',
            ];

            foreach ($validServices as $service) {
                expect($this->factory->supports($service))
                    ->toBeTrue("Service '{$service}' should be supported");
            }
        });

        test('supports returns false for invalid service names', function () {
            $invalidServices = [
                'invalid',
                'nonexistent',
                'emails',
                'webhooks',
                'issuedEInvoices',
                '',
                'CLIENTS', // Case sensitive
                'products-api',
            ];

            foreach ($invalidServices as $service) {
                expect($this->factory->supports($service))
                    ->toBeFalse("Service '{$service}' should not be supported");
            }
        });
    });

    describe('service creation', function () {
        test('creates ClientsApi instance', function () {
            $result = $this->factory->make('clients');

            expect($result)->toBeInstanceOf(ClientsApi::class);
        });

        test('creates CompaniesApi instance', function () {
            $result = $this->factory->make('companies');

            expect($result)->toBeInstanceOf(CompaniesApi::class);
        });

        test('creates InfoApi instance', function () {
            $result = $this->factory->make('info');

            expect($result)->toBeInstanceOf(InfoApi::class);
        });

        test('creates IssuedDocumentsApi instance', function () {
            $result = $this->factory->make('issuedDocuments');

            expect($result)->toBeInstanceOf(IssuedDocumentsApi::class);
        });

        test('creates ProductsApi instance', function () {
            $result = $this->factory->make('products');

            expect($result)->toBeInstanceOf(ProductsApi::class);
        });

        test('creates ReceiptsApi instance', function () {
            $result = $this->factory->make('receipts');

            expect($result)->toBeInstanceOf(ReceiptsApi::class);
        });

        test('creates ReceivedDocumentsApi instance', function () {
            $result = $this->factory->make('receivedDocuments');

            expect($result)->toBeInstanceOf(ReceivedDocumentsApi::class);
        });

        test('creates SuppliersApi instance', function () {
            $result = $this->factory->make('suppliers');

            expect($result)->toBeInstanceOf(SuppliersApi::class);
        });

        test('creates TaxesApi instance', function () {
            $result = $this->factory->make('taxes');

            expect($result)->toBeInstanceOf(TaxesApi::class);
        });

        test('creates UserApi instance', function () {
            $result = $this->factory->make('user');

            expect($result)->toBeInstanceOf(UserApi::class);
        });

        test('creates SettingsApi instance', function () {
            $result = $this->factory->make('settings');

            expect($result)->toBeInstanceOf(SettingsApi::class);
        });

        test('creates ArchiveApi instance', function () {
            $result = $this->factory->make('archive');

            expect($result)->toBeInstanceOf(ArchiveApi::class);
        });

        test('creates CashbookApi instance', function () {
            $result = $this->factory->make('cashbook');

            expect($result)->toBeInstanceOf(CashbookApi::class);
        });

        test('creates PriceListsApi instance', function () {
            $result = $this->factory->make('priceLists');

            expect($result)->toBeInstanceOf(PriceListsApi::class);
        });
    });

    describe('error handling', function () {
        test('throws UnsupportedServiceException for unsupported services', function () {
            expect(fn () => $this->factory->make('unsupported'))
                ->toThrow(UnsupportedServiceException::class, "Service 'unsupported' is not supported");
        });

        test('throws exception for empty service name', function () {
            expect(fn () => $this->factory->make(''))
                ->toThrow(UnsupportedServiceException::class, "Service '' is not supported");
        });

        test('throws exception for case-sensitive service names', function () {
            expect(fn () => $this->factory->make('CLIENTS'))
                ->toThrow(UnsupportedServiceException::class, "Service 'CLIENTS' is not supported");
        });

        test('throws exception for invalid service variations', function () {
            $invalidVariations = [
                'client', // singular
                'clientsApi', // with suffix
                'clients-api', // with dash
                'Clients', // capitalized
            ];

            foreach ($invalidVariations as $invalid) {
                expect(fn () => $this->factory->make($invalid))
                    ->toThrow(UnsupportedServiceException::class, "Service '{$invalid}' is not supported");
            }
        });
    });

    describe('dependency injection', function () {
        test('passes dependencies to created API services', function () {
            $httpClient = new HttpClient();
            $configuration = new Configuration();
            $headerSelector = new HeaderSelector();
            
            $factory = new FattureInCloudApiServiceFactory(
                $httpClient,
                $configuration,
                $headerSelector
            );

            $clientsApi = $factory->make('clients');

            expect($clientsApi)->toBeInstanceOf(ClientsApi::class);
        });

        test('created services have consistent dependency injection', function () {
            $services = ['clients', 'companies', 'info', 'products'];
            
            foreach ($services as $serviceName) {
                $api = $this->factory->make($serviceName);
                
                // Each API should be instantiated successfully with the provided dependencies
                expect($api)->toBeObject();
            }
        });
    });

    describe('service instantiation', function () {
        test('creates new instances on each call', function () {
            $clients1 = $this->factory->make('clients');
            $clients2 = $this->factory->make('clients');

            expect($clients1)->not()->toBe($clients2);
            expect($clients1)->toEqual($clients2);
        });

        test('different services return different instances', function () {
            $clientsApi = $this->factory->make('clients');
            $companiesApi = $this->factory->make('companies');

            expect($clientsApi)->not()->toBe($companiesApi);
            expect($clientsApi)->toBeInstanceOf(ClientsApi::class);
            expect($companiesApi)->toBeInstanceOf(CompaniesApi::class);
        });
    });

    describe('service mapping completeness', function () {
        test('all supported services can be created successfully', function () {
            $supportedServices = $this->factory->getSupportedServices();
            
            foreach ($supportedServices as $serviceName) {
                $service = $this->factory->make($serviceName);
                
                expect($service)->toBeObject();
                expect($service)->not()->toBeNull();
            }
        });

        test('service mapping covers expected API classes', function () {
            $expectedMappings = [
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

            foreach ($expectedMappings as $serviceName => $expectedClass) {
                $service = $this->factory->make($serviceName);
                
                expect($service)->toBeInstanceOf($expectedClass);
            }
        });
    });

    describe('configuration handling', function () {
        test('works with different Configuration instances', function () {
            $config1 = new Configuration();
            $config1->setAccessToken('token1');
            
            $config2 = new Configuration();
            $config2->setAccessToken('token2');

            $factory1 = new FattureInCloudApiServiceFactory(
                new HttpClient(),
                $config1,
                new HeaderSelector()
            );

            $factory2 = new FattureInCloudApiServiceFactory(
                new HttpClient(),
                $config2,
                new HeaderSelector()
            );

            $clients1 = $factory1->make('clients');
            $clients2 = $factory2->make('clients');

            expect($clients1)->toBeInstanceOf(ClientsApi::class);
            expect($clients2)->toBeInstanceOf(ClientsApi::class);
        });

        test('accepts empty Configuration', function () {
            $factory = new FattureInCloudApiServiceFactory(
                new HttpClient(),
                new Configuration(),
                new HeaderSelector()
            );

            $clientsApi = $factory->make('clients');

            expect($clientsApi)->toBeInstanceOf(ClientsApi::class);
        });
    });
});