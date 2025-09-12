<?php

namespace Codeman\FattureInCloud\Services;

use Codeman\FattureInCloud\Contracts\ApiServiceFactory as ApiServiceFactoryContract;
use Codeman\FattureInCloud\Contracts\TokenStorage;
use Codeman\FattureInCloud\Exceptions\UnsupportedServiceException;
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
use FattureInCloud\HeaderSelector;
use GuzzleHttp\Client as HttpClient;

class FattureInCloudApiServiceFactory implements ApiServiceFactoryContract
{
    private array $serviceMapping = [
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

    public function __construct(
        private readonly HttpClient $httpClient,
        private readonly Configuration $configuration,
        private readonly HeaderSelector $headerSelector,
        private readonly ?TokenStorage $tokenStorage = null,
        private readonly string $contextKey = 'default'
    ) {}

    public function make(string $serviceName): object
    {
        if (! $this->supports($serviceName)) {
            throw new UnsupportedServiceException("Service '{$serviceName}' is not supported");
        }

        // Create a fresh configuration with current token if TokenStorage is available
        $config = clone $this->configuration;

        if ($this->tokenStorage) {
            $currentToken = $this->tokenStorage->getAccessToken($this->contextKey);
            if ($currentToken) {
                $config->setAccessToken($currentToken);
            }
        }

        $apiClass = $this->serviceMapping[$serviceName];

        return new $apiClass(
            $this->httpClient,
            $config,
            $this->headerSelector
        );
    }

    public function supports(string $serviceName): bool
    {
        return isset($this->serviceMapping[$serviceName]);
    }

    public function getSupportedServices(): array
    {
        return array_keys($this->serviceMapping);
    }
}
