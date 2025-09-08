<?php

namespace Codeman\LaravelFattureInCloudPhpSdk;

use Codeman\LaravelFattureInCloudPhpSdk\Contracts\ApiServiceFactoryInterface;
use Codeman\LaravelFattureInCloudPhpSdk\Contracts\OAuth2ManagerInterface;
use Codeman\LaravelFattureInCloudPhpSdk\Contracts\TokenStorageInterface;
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
use FattureInCloud\OAuth2\OAuth2TokenResponse;

class LaravelFattureInCloudPhpSdk
{
    private ?string $currentCompanyId = null;

    public function __construct(
        private OAuth2ManagerInterface $oauthManager,
        private TokenStorageInterface $tokenStorage,
        private ApiServiceFactoryInterface $apiFactory,
        private string $contextKey = 'default'
    ) {}

    public function auth(): OAuth2ManagerInterface
    {
        return $this->oauthManager;
    }

    public function getAuthorizationUrl(array $scopes, ?string $state = null): string
    {
        return $this->oauthManager->getAuthorizationUrl($scopes, $state);
    }

    public function fetchToken(string $code, string $state): OAuth2TokenResponse
    {
        $tokenResponse = $this->oauthManager->fetchToken($code, $state);
        $this->tokenStorage->store($this->contextKey, $tokenResponse);

        return $tokenResponse;
    }

    public function refreshToken(): ?OAuth2TokenResponse
    {
        $refreshToken = $this->tokenStorage->getRefreshToken($this->contextKey);

        if (! $refreshToken) {
            return null;
        }

        try {
            $tokenResponse = $this->oauthManager->refreshToken($refreshToken);

            if ($tokenResponse) {
                $this->tokenStorage->store($this->contextKey, $tokenResponse);
            }

            return $tokenResponse;
        } catch (\Exception $e) {
            $this->tokenStorage->clear($this->contextKey);
            throw $e;
        }
    }

    public function setCompany(int $companyId): self
    {
        $this->currentCompanyId = (string) $companyId;

        return $this;
    }

    public function getCompany(): ?string
    {
        return $this->currentCompanyId;
    }

    public function isTokenExpired(): bool
    {
        return $this->tokenStorage->isExpired($this->contextKey);
    }

    public function clearTokens(): void
    {
        $this->tokenStorage->clear($this->contextKey);
    }

    public function clients(): ClientsApi
    {
        $this->ensureValidToken();

        return $this->apiFactory->make('clients');
    }

    public function companies(): CompaniesApi
    {
        $this->ensureValidToken();

        return $this->apiFactory->make('companies');
    }

    public function info(): InfoApi
    {
        $this->ensureValidToken();

        return $this->apiFactory->make('info');
    }

    public function issuedDocuments(): IssuedDocumentsApi
    {
        $this->ensureValidToken();

        return $this->apiFactory->make('issuedDocuments');
    }

    public function products(): ProductsApi
    {
        $this->ensureValidToken();

        return $this->apiFactory->make('products');
    }

    public function receipts(): ReceiptsApi
    {
        $this->ensureValidToken();

        return $this->apiFactory->make('receipts');
    }

    public function receivedDocuments(): ReceivedDocumentsApi
    {
        $this->ensureValidToken();

        return $this->apiFactory->make('receivedDocuments');
    }

    public function suppliers(): SuppliersApi
    {
        $this->ensureValidToken();

        return $this->apiFactory->make('suppliers');
    }

    public function taxes(): TaxesApi
    {
        $this->ensureValidToken();

        return $this->apiFactory->make('taxes');
    }

    public function user(): UserApi
    {
        $this->ensureValidToken();

        return $this->apiFactory->make('user');
    }

    public function settings(): SettingsApi
    {
        $this->ensureValidToken();

        return $this->apiFactory->make('settings');
    }

    public function archiveDocuments(): ArchiveApi
    {
        $this->ensureValidToken();

        return $this->apiFactory->make('archiveDocuments');
    }

    public function cashbook(): CashbookApi
    {
        $this->ensureValidToken();

        return $this->apiFactory->make('cashbook');
    }

    public function priceLists(): PriceListsApi
    {
        $this->ensureValidToken();

        return $this->apiFactory->make('priceLists');
    }

    private function ensureValidToken(): void
    {
        if ($this->isTokenExpired() && $this->oauthManager->isInitialized()) {
            $this->refreshToken();
        }
    }
}
