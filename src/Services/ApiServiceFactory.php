<?php

namespace Codeman\LaravelFattureInCloudPhpSdk\Services;

use Codeman\LaravelFattureInCloudPhpSdk\Contracts\ApiServiceFactoryInterface;
use Codeman\LaravelFattureInCloudPhpSdk\Exceptions\UnsupportedServiceException;
use FattureInCloud\Configuration;
use FattureInCloud\HeaderSelector;
use GuzzleHttp\Client as HttpClient;

class ApiServiceFactory implements ApiServiceFactoryInterface
{
    private array $serviceMapping = [
        'clients' => \FattureInCloud\Api\ClientsApi::class,
        'companies' => \FattureInCloud\Api\CompaniesApi::class,
        'info' => \FattureInCloud\Api\InfoApi::class,
        'issuedDocuments' => \FattureInCloud\Api\IssuedDocumentsApi::class,
        'products' => \FattureInCloud\Api\ProductsApi::class,
        'receipts' => \FattureInCloud\Api\ReceiptsApi::class,
        'receivedDocuments' => \FattureInCloud\Api\ReceivedDocumentsApi::class,
        'suppliers' => \FattureInCloud\Api\SuppliersApi::class,
        'taxes' => \FattureInCloud\Api\TaxesApi::class,
        'user' => \FattureInCloud\Api\UserApi::class,
        'settings' => \FattureInCloud\Api\SettingsApi::class,
        'archiveDocuments' => \FattureInCloud\Api\ArchiveApi::class,
        'cashbook' => \FattureInCloud\Api\CashbookApi::class,
        'priceLists' => \FattureInCloud\Api\PriceListsApi::class,
    ];

    private ?string $companyId = null;

    public function __construct(
        private HttpClient $httpClient,
        private Configuration $configuration,
        private HeaderSelector $headerSelector
    ) {}

    public function make(string $serviceName): object
    {
        if (! $this->supports($serviceName)) {
            throw new UnsupportedServiceException("Service '{$serviceName}' is not supported");
        }

        $apiClass = $this->serviceMapping[$serviceName];

        return new $apiClass(
            $this->httpClient,
            $this->configuration,
            $this->headerSelector
        );
    }

    public function supports(string $serviceName): bool
    {
        return isset($this->serviceMapping[$serviceName]);
    }

    public function setCompanyId(?string $companyId): self
    {
        $this->companyId = $companyId;

        return $this;
    }

    public function getSupportedServices(): array
    {
        return array_keys($this->serviceMapping);
    }
}
