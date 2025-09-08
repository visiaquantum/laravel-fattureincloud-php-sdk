# Laravel Fatture in Cloud SDK

[![Latest Version on Packagist](https://img.shields.io/packagist/v/codeman/laravel-fattureincloud-php-sdk.svg?style=flat-square)](https://packagist.org/packages/codeman/laravel-fattureincloud-php-sdk)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/visiaquantum/laravel-fattureincloud-php-sdk/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/visiaquantum/laravel-fattureincloud-php-sdk/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/visiaquantum/laravel-fattureincloud-php-sdk/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/visiaquantum/laravel-fattureincloud-php-sdk/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/codeman/laravel-fattureincloud-php-sdk.svg?style=flat-square)](https://packagist.org/packages/codeman/laravel-fattureincloud-php-sdk)

A clean, Laravel-native wrapper for the official Fatture in Cloud PHP SDK. This package provides a simple and expressive interface for interacting with the Fatture in Cloud API while following Laravel conventions and best practices.

## Features

- 🚀 **Laravel Native**: Built following Laravel conventions and standards
- 🔐 **OAuth2 Integration**: Complete OAuth2 authorization code flow support
- 💾 **Smart Token Management**: Automatic token storage, refresh, and encryption
- 🏗️ **Clean Architecture**: Interfaces and dependency injection throughout
- 📦 **All API Services**: Full access to all Fatture in Cloud API endpoints
- 🛡️ **Secure**: Built-in security features and encrypted token storage
- 🧪 **Well Tested**: Comprehensive test coverage with Pest PHP

## Requirements

- PHP 8.3 or higher
- Laravel 11.x or 12.x
- Fatture in Cloud Developer Account

## Installation

You can install the package via Composer:

```bash
composer require codeman/laravel-fattureincloud-php-sdk
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag="fatture-in-cloud-config"
```

## Configuration

### Environment Variables

Add these variables to your `.env` file:

```env
# OAuth2 Credentials (for OAuth2 flow)
FATTUREINCLOUD_CLIENT_ID=your_client_id
FATTUREINCLOUD_CLIENT_SECRET=your_client_secret
FATTUREINCLOUD_REDIRECT_URL=https://yourapp.com/fattureincloud/callback

# OR Manual Authentication (takes precedence if set)
FATTUREINCLOUD_ACCESS_TOKEN=your_access_token
```

### Configuration File

The published configuration file (`config/fatture-in-cloud.php`) includes comprehensive OAuth2 settings:

```php
return [
    'client_id' => env('FATTUREINCLOUD_CLIENT_ID'),
    'client_secret' => env('FATTUREINCLOUD_CLIENT_SECRET'),
    'redirect_url' => env('FATTUREINCLOUD_REDIRECT_URL'),
    'access_token' => env('FATTUREINCLOUD_ACCESS_TOKEN'),
];
```

## Usage

### Facade Usage

```php
use Codeman\FattureInCloud\Facades\FattureInCloud;

// OAuth2 Authentication Flow
$authUrl = FattureInCloud::getAuthorizationUrl(['entity.clients:r', 'entity.clients:a']);
// Redirect user to $authUrl

// After callback, exchange code for token
$token = FattureInCloud::fetchToken($code, $state);

// Set company context (required for most operations)
FattureInCloud::setCompany($companyId);

// Use API services
$clients = FattureInCloud::clients()->listClients($companyId);
$products = FattureInCloud::products()->listProducts($companyId);
```

### Dependency Injection

```php
use Codeman\FattureInCloud\FattureInCloudSdk;

class InvoiceService
{
    public function __construct(
        private FattureInCloudSdk $fatture
    ) {}
    
    public function getClients(int $companyId): array
    {
        return $this->fatture
            ->setCompany($companyId)
            ->clients()
            ->listClients($companyId)
            ->getData();
    }
}
```

### Available API Services

The package provides access to all Fatture in Cloud API endpoints:

```php
// Core Entities
FattureInCloud::clients()           // ClientsApi
FattureInCloud::suppliers()         // SuppliersApi  
FattureInCloud::products()          // ProductsApi
FattureInCloud::issuedDocuments()   // IssuedDocumentsApi
FattureInCloud::receivedDocuments() // ReceivedDocumentsApi
FattureInCloud::receipts()          // ReceiptsApi

// Company & User Management
FattureInCloud::companies()         // CompaniesApi
FattureInCloud::user()              // UserApi
FattureInCloud::info()              // InfoApi

// Settings & Configuration  
FattureInCloud::settings()          // SettingsApi
FattureInCloud::taxes()             // TaxesApi
FattureInCloud::priceLists()        // PriceListsApi

// Additional Services
FattureInCloud::archiveDocuments()  // ArchiveApi
FattureInCloud::cashbook()          // CashbookApi
```

### OAuth2 Authentication Flow

#### 1. Generate Authorization URL

```php
$scopes = [
    'entity.clients:r',
    'entity.clients:a', 
    'entity.products:r',
    'issued_documents.invoices:r',
    'issued_documents.invoices:a'
];

$authUrl = FattureInCloud::getAuthorizationUrl($scopes);
return redirect($authUrl);
```

#### 2. Handle Callback

```php
// In your callback route
public function callback(Request $request)
{
    $code = $request->input('code');
    $state = $request->input('state');
    
    try {
        $token = FattureInCloud::fetchToken($code, $state);
        
        // Token is automatically stored and encrypted
        // Ready to use API services
        
        return redirect('/dashboard')->with('success', 'Connected to Fatture in Cloud!');
    } catch (\Exception $e) {
        return redirect('/settings')->with('error', 'Authentication failed: ' . $e->getMessage());
    }
}
```

#### 3. Using API Services

```php
// Set company context
FattureInCloud::setCompany($companyId);

// Create a new client
$newClient = new Client([
    'name' => 'New Client Ltd',
    'code' => 'CLIENT001',
    'type' => ClientType::COMPANY,
]);

$createdClient = FattureInCloud::clients()
    ->createClient($companyId, $newClient);

// List all products
$products = FattureInCloud::products()
    ->listProducts($companyId);

// Create an invoice
$invoice = new IssuedDocument([
    'type' => IssuedDocumentType::INVOICE,
    'entity' => $client,
    'items_list' => [$invoiceItem],
    'payments_list' => [$payment],
]);

$createdInvoice = FattureInCloud::issuedDocuments()
    ->createIssuedDocument($companyId, $invoice);
```

### Token Management

```php
// Check if token is expired
if (FattureInCloud::isTokenExpired()) {
    // Tokens are automatically refreshed when needed
    // Manual refresh if required:
    $newToken = FattureInCloud::refreshToken();
}

// Clear stored tokens (logout)
FattureInCloud::clearTokens();
```

### Manual Authentication

If you prefer to use a manually generated access token instead of OAuth2:

```env
FATTUREINCLOUD_ACCESS_TOKEN=your_access_token_here
```

When an access token is configured, OAuth2 settings are ignored and the SDK will use the token directly.

## Architecture

The package follows clean architecture principles:

```
Codeman\FattureInCloud\
├── FattureInCloudSdk              # Main SDK class
├── FattureInCloudServiceProvider  # Laravel service provider  
├── Contracts\                     # Interface definitions
│   ├── OAuth2Manager              # OAuth2 operations contract
│   ├── StateManager               # State management contract
│   ├── TokenStorage               # Token storage contract
│   └── ApiServiceFactory          # API service creation contract
├── Services\                      # Concrete implementations
│   ├── OAuth2AuthorizationCodeManager # OAuth2 implementation
│   ├── SessionStateManager           # Session-based state storage
│   ├── CacheTokenStorage             # Encrypted cache token storage
│   └── FattureInCloudApiServiceFactory # API service factory
├── Exceptions\                    # Custom exceptions
│   ├── OAuth2Exception            # OAuth2-related errors
│   └── UnsupportedServiceException # Invalid API service errors
└── Facades\
    └── FattureInCloud             # Laravel facade
```

## Testing

Run the test suite:

```bash
composer test
```

Run with coverage:

```bash
vendor/bin/pest --coverage
```

Run static analysis:

```bash
composer analyse
```

## Code Quality

Format code:

```bash
composer format
```

## Security

- All tokens are automatically encrypted before storage
- Secure session-based state management for OAuth2 flows
- Environment-based configuration prevents credential exposure
- Automatic token refresh prevents expired token issues

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Mattia Migliorini](https://github.com/deshack)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
