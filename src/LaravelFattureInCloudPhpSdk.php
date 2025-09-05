<?php

namespace Codeman\LaravelFattureInCloudPhpSdk;

use FattureInCloud\Configuration;
use FattureInCloud\HeaderSelector;
use FattureInCloud\OAuth2\OAuth2AuthorizationCode\OAuth2AuthorizationCodeManager;
use FattureInCloud\OAuth2\OAuth2Error;
use FattureInCloud\OAuth2\OAuth2TokenResponse;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class LaravelFattureInCloudPhpSdk
{
    protected HttpClient $httpClient;

    protected Configuration $configuration;

    protected ?OAuth2AuthorizationCodeManager $oauthManager = null;

    protected ConfigRepository $config;

    protected ?string $currentCompanyId = null;

    public function __construct(ConfigRepository $config)
    {
        $this->config = $config;
        $this->initializeClient();
    }

    protected function initializeClient(): void
    {
        $this->configuration = new Configuration;
        $this->httpClient = new HttpClient;

        $accessToken = $this->getAccessToken();
        if ($accessToken) {
            $this->configuration->setAccessToken($accessToken);
        }

        if ($this->shouldUseOAuth2()) {
            $this->initializeOAuth2Manager();
        }
    }

    protected function initializeOAuth2Manager(): void
    {
        $clientId = $this->config->get('fattureincloud-php-sdk.client_id');
        $clientSecret = $this->config->get('fattureincloud-php-sdk.client_secret');
        $redirectUrl = $this->getRedirectUrl();

        if (! $clientId || ! $clientSecret) {
            throw new \InvalidArgumentException('OAuth2 client_id and client_secret are required for OAuth2 authentication');
        }

        $this->oauthManager = new OAuth2AuthorizationCodeManager(
            $clientId,
            $clientSecret,
            $redirectUrl
        );
    }

    protected function shouldUseOAuth2(): bool
    {
        return ! $this->config->get('fattureincloud-php-sdk.access_token');
    }

    protected function getAccessToken(): ?string
    {
        $accessToken = $this->config->get('fattureincloud-php-sdk.access_token');

        if ($accessToken) {
            return $accessToken;
        }

        return $this->getStoredAccessToken();
    }

    protected function getRedirectUrl(): string
    {
        $redirectUrl = $this->config->get('fattureincloud-php-sdk.redirect_url');

        if ($redirectUrl) {
            return $redirectUrl;
        }

        return config('app.url').'/fatture-in-cloud/callback';
    }

    public function getAuthorizationUrl(array $scopes, ?string $state = null): string
    {
        if (! $this->oauthManager) {
            throw new \LogicException('OAuth2 manager not initialized. Cannot generate authorization URL.');
        }

        if (! $state) {
            $state = Str::random(40);
            $this->storeState($state);
        }

        return $this->oauthManager->getAuthorizationUrl($scopes, $state);
    }

    public function fetchToken(string $code, string $state): OAuth2TokenResponse
    {
        if (! $this->oauthManager) {
            throw new \LogicException('OAuth2 manager not initialized. Cannot fetch token.');
        }

        if (! $this->validateState($state)) {
            throw new \InvalidArgumentException('Invalid state parameter. Possible CSRF attack.');
        }

        $tokenResponse = $this->oauthManager->fetchToken($code);

        if ($tokenResponse instanceof OAuth2Error) {
            throw new \RuntimeException('OAuth2 error: '.$tokenResponse->getError().' - '.$tokenResponse->getErrorDescription());
        }

        $this->storeTokenResponse($tokenResponse);
        $this->clearState();

        $this->configuration->setAccessToken($tokenResponse->getAccessToken());

        return $tokenResponse;
    }

    public function refreshToken(): ?OAuth2TokenResponse
    {
        if (! $this->oauthManager) {
            throw new \LogicException('OAuth2 manager not initialized. Cannot refresh token.');
        }

        $refreshToken = $this->getStoredRefreshToken();

        if (! $refreshToken) {
            return null;
        }

        try {
            $tokenResponse = $this->oauthManager->refreshToken($refreshToken);

            if ($tokenResponse instanceof OAuth2Error) {
                throw new \RuntimeException('OAuth2 refresh error: '.$tokenResponse->getError().' - '.$tokenResponse->getErrorDescription());
            }

            $this->storeTokenResponse($tokenResponse);
            $this->configuration->setAccessToken($tokenResponse->getAccessToken());

            return $tokenResponse;
        } catch (\Exception $e) {
            $this->clearStoredTokens();
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
        $expiresAt = $this->getStoredTokenExpiration();

        if (! $expiresAt) {
            return true;
        }

        return now()->timestamp >= $expiresAt;
    }

    protected function storeState(string $state): void
    {
        Session::put('fatture_in_cloud_oauth_state', $state);
    }

    protected function validateState(string $state): bool
    {
        $storedState = Session::get('fatture_in_cloud_oauth_state');

        return $storedState && hash_equals($storedState, $state);
    }

    protected function clearState(): void
    {
        Session::forget('fatture_in_cloud_oauth_state');
    }

    protected function storeTokenResponse(OAuth2TokenResponse $tokenResponse): void
    {
        $encryptedData = encrypt([
            'access_token' => $tokenResponse->getAccessToken(),
            'refresh_token' => $tokenResponse->getRefreshToken(),
            'expires_at' => now()->addSeconds($tokenResponse->getExpiresIn())->timestamp,
        ]);

        Cache::put('fatture_in_cloud_tokens', $encryptedData, now()->addYear());
    }

    protected function getStoredAccessToken(): ?string
    {
        $tokens = $this->getStoredTokens();

        return $tokens['access_token'] ?? null;
    }

    protected function getStoredRefreshToken(): ?string
    {
        $tokens = $this->getStoredTokens();

        return $tokens['refresh_token'] ?? null;
    }

    protected function getStoredTokenExpiration(): ?int
    {
        $tokens = $this->getStoredTokens();

        return $tokens['expires_at'] ?? null;
    }

    protected function getStoredTokens(): array
    {
        $encryptedData = Cache::get('fatture_in_cloud_tokens');

        if (! $encryptedData) {
            return [];
        }

        try {
            return decrypt($encryptedData);
        } catch (\Exception $e) {
            $this->clearStoredTokens();

            return [];
        }
    }

    protected function clearStoredTokens(): void
    {
        Cache::forget('fatture_in_cloud_tokens');
    }

    public function __call(string $method, array $arguments)
    {
        if ($this->isTokenExpired() && $this->shouldUseOAuth2()) {
            $this->refreshToken();
        }

        $apiClass = $this->resolveApiClass($method);

        if (! $apiClass) {
            throw new \BadMethodCallException("Method {$method} not found");
        }

        $apiInstance = new $apiClass(
            $this->httpClient,
            $this->configuration,
            new HeaderSelector
        );

        return $apiInstance;
    }

    protected function resolveApiClass(string $method): ?string
    {
        $apiMappings = [
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

        return $apiMappings[$method] ?? null;
    }

    public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }

    public function getHttpClient(): HttpClient
    {
        return $this->httpClient;
    }
}
