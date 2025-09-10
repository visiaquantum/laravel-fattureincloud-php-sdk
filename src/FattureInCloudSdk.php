<?php

namespace Codeman\FattureInCloud;

use Codeman\FattureInCloud\Contracts\ApiServiceFactory;
use Codeman\FattureInCloud\Contracts\OAuth2Manager;
use Codeman\FattureInCloud\Contracts\TokenStorage;
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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * @SuppressWarnings("php:S1448")
 */
class FattureInCloudSdk
{
    private ?string $currentCompanyId = null;

    public function __construct(
        private OAuth2Manager $oauthManager,
        private TokenStorage $tokenStorage,
        private ApiServiceFactory $apiFactory,
        private string $contextKey = 'default'
    ) {}

    public function auth(): OAuth2Manager
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

    /**
     * Generate a redirect response to the Fatture in Cloud OAuth2 authorization page.
     *
     * This is a convenience method that combines getAuthorizationUrl() with Laravel's
     * redirect() helper to return a RedirectResponse that can be used directly from
     * controllers.
     *
     * @param  array  $scopes  Array of OAuth2 scopes to request (e.g., [Scope::ENTITY_CLIENTS_READ])
     * @return RedirectResponse Laravel redirect response to the authorization URL
     *
     * @throws \LogicException If OAuth2 manager is not properly initialized
     *
     * @example
     * ```php
     * // In a controller
     * public function auth(FattureInCloudSdk $sdk)
     * {
     *     return $sdk->redirectToAuthorization([
     *         Scope::ENTITY_CLIENTS_READ,
     *         Scope::ISSUED_DOCUMENTS_INVOICES_ALL
     *     ]);
     * }
     * ```
     */
    public function redirectToAuthorization(array $scopes): RedirectResponse
    {
        if (! $this->oauthManager->isInitialized()) {
            throw new \LogicException('OAuth2 manager is not initialized. Please ensure FATTUREINCLOUD_CLIENT_ID and FATTUREINCLOUD_CLIENT_SECRET are configured.');
        }

        $authUrl = $this->oauthManager->getAuthorizationUrl($scopes, null);

        return redirect($authUrl);
    }

    /**
     * Handle the OAuth2 authorization callback from Fatture in Cloud.
     *
     * This method processes the callback request from the OAuth2 authorization flow,
     * validates the parameters, exchanges the authorization code for tokens, and
     * stores the tokens automatically.
     *
     * @param  Request  $request  Laravel HTTP request containing callback parameters
     * @return OAuth2TokenResponse The token response containing access and refresh tokens
     *
     * @throws \InvalidArgumentException If OAuth2 error occurred or required parameters are missing
     *
     * @example
     * ```php
     * // In a controller handling the callback route
     * public function callback(Request $request, FattureInCloudSdk $sdk)
     * {
     *     try {
     *         $tokenResponse = $sdk->handleOAuth2Callback($request);
     *         return redirect('/dashboard')->with('success', 'Authentication successful!');
     *     } catch (\InvalidArgumentException $e) {
     *         return redirect('/auth')->with('error', 'Authentication failed: ' . $e->getMessage());
     *     }
     * }
     * ```
     */
    public function handleOAuth2Callback(Request $request): OAuth2TokenResponse
    {
        $code = $request->get('code');
        $state = $request->get('state');
        $error = $request->get('error');
        $errorDescription = $request->get('error_description');

        if ($error) {
            throw new \InvalidArgumentException("OAuth2 authorization failed: {$error}".($errorDescription ? " - {$errorDescription}" : ''));
        }

        if (! $code || ! $state) {
            throw new \InvalidArgumentException('Missing required OAuth2 callback parameters: code and state are required');
        }

        return $this->fetchToken($code, $state);
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

    public function archive(): ArchiveApi
    {
        $this->ensureValidToken();

        return $this->apiFactory->make('archive');
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
