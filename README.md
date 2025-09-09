# Laravel Fatture in Cloud PHP SDK

[![Latest Version on Packagist](https://img.shields.io/packagist/v/visiaquantum/laravel-fattureincloud-php-sdk.svg?style=flat-square)](https://packagist.org/packages/visiaquantum/laravel-fattureincloud-php-sdk)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/visiaquantum/laravel-fattureincloud-php-sdk/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/visiaquantum/laravel-fattureincloud-php-sdk/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/visiaquantum/laravel-fattureincloud-php-sdk/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/visiaquantum/laravel-fattureincloud-php-sdk/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/visiaquantum/laravel-fattureincloud-php-sdk.svg?style=flat-square)](https://packagist.org/packages/visiaquantum/laravel-fattureincloud-php-sdk)

A Laravel package that provides a clean, Laravel-native wrapper around the official [Fatture in Cloud PHP SDK](https://github.com/fattureincloud/fattureincloud-php-sdk). This package abstracts the complexity of the base SDK and offers a simplified, Laravel-integrated developer experience for interacting with the Fatture in Cloud API.

The package follows Laravel ecosystem conventions with a clean, simplified architecture that provides OAuth2 authentication flows, token management, and service abstraction for seamless API integration.

## Features

- 🚀 **Laravel Native**: Built following Laravel conventions and standards
- 🔐 **OAuth2 Integration**: Complete OAuth2 authorization code flow support
- 💾 **Smart Token Management**: Automatic token storage, refresh, and encryption
- 🏗️ **Clean Architecture**: Interfaces and dependency injection throughout
- 📦 **All API Services**: Full access to all Fatture in Cloud API endpoints
- 🛡️ **Secure**: Built-in security features and encrypted token storage
- 🧪 **Well Tested**: Comprehensive test coverage with Pest PHP

## Requirements

- PHP 8.4 or higher
- Laravel 11.x or 12.x
- Fatture in Cloud Developer Account

## Installation

You can install the package via Composer:

```bash
composer require visiaquantum/laravel-fattureincloud-php-sdk
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag="laravel-fattureincloud-php-sdk-config"
```

## Configuration

### Environment Variables

Add these variables to your `.env` file:

```env
# OAuth2 Credentials (for OAuth2 flow)
FATTUREINCLOUD_CLIENT_ID=your_client_id
FATTUREINCLOUD_CLIENT_SECRET=your_client_secret
FATTUREINCLOUD_REDIRECT_URL=https://yourapp.com/fatture-in-cloud/callback

# OR Manual Authentication (takes precedence if set)
FATTUREINCLOUD_ACCESS_TOKEN=your_access_token
```

### Configuration File

The published configuration file (`config/fatture-in-cloud.php`) includes comprehensive OAuth2 settings:

```php
return [
    'client_id' => env('FATTUREINCLOUD_CLIENT_ID'),
    'client_secret' => env('FATTUREINCLOUD_CLIENT_SECRET'),
    'redirect_url' => env('FATTUREINCLOUD_REDIRECT_URL', config('app.url') . '/fatture-in-cloud/callback'),
    'access_token' => env('FATTUREINCLOUD_ACCESS_TOKEN'),
    
    // OAuth2 settings
    'base_uri' => env('FATTUREINCLOUD_BASE_URI', 'https://api-v2.fattureincloud.it'),
    'oauth2_base_uri' => env('FATTUREINCLOUD_OAUTH2_BASE_URI', 'https://api-v2.fattureincloud.it'),
    
    // Token storage settings
    'token_cache_prefix' => 'fattureincloud_tokens',
    'state_session_key' => 'fattureincloud_oauth2_state',
];
```

### OAuth2 Callback Route

The package automatically registers the OAuth2 callback route at `/fatture-in-cloud/callback` (named `fatture-in-cloud.callback`) using a single action invokable controller. This route:

- Processes OAuth2 authorization callbacks from Fatture in Cloud
- Handles both successful authorizations and error responses
- Validates CSRF state parameters to prevent attacks
- Exchanges authorization codes for access/refresh tokens
- Stores tokens securely using Laravel's encrypted cache
- Returns structured JSON responses for success/error scenarios

The route is registered as: `Route::get('/fatture-in-cloud/callback', OAuth2CallbackController::class)`

## Usage

### Facade Usage

```php
use Codeman\FattureInCloud\Facades\FattureInCloud;

// OAuth2 Authentication Flow
$authUrl = FattureInCloud::getAuthorizationUrl(['entity.clients:r', 'entity.clients:a']);
// Redirect user to $authUrl

// After callback, exchange code for token
$token = FattureInCloud::handleCallback($request);

// Create API services
$companiesApi = FattureInCloud::createService('companies');
$clientsApi = FattureInCloud::createService('clients');
$productsApi = FattureInCloud::createService('products');

// Use API services
$companies = $companiesApi->listUserCompanies();
$clients = $clientsApi->listClients($companyId);
$products = $productsApi->listProducts($companyId);
```

### Dependency Injection

```php
use Codeman\FattureInCloud\FattureInCloudSdk;

class InvoiceService
{
    public function __construct(
        private FattureInCloudSdk $sdk
    ) {}
    
    public function getClients(int $companyId): array
    {
        $clientsApi = $this->sdk->createService('clients');
        
        return $clientsApi
            ->listClients($companyId)
            ->getData();
    }
    
    public function createInvoice(int $companyId, array $invoiceData): object
    {
        $issuedDocumentsApi = $this->sdk->createService('issuedDocuments');
        
        return $issuedDocumentsApi
            ->createIssuedDocument($companyId, $invoiceData)
            ->getData();
    }
}
```

### Available API Services

The package provides access to 13+ Fatture in Cloud API services through the factory pattern:

| Service Key | API Class | Purpose |
|-------------|-----------|---------|
| `clients` | `ClientsApi` | Customer/client management |
| `companies` | `CompaniesApi` | Company information & settings |
| `info` | `InfoApi` | System information & metadata |
| `issuedDocuments` | `IssuedDocumentsApi` | Invoices, quotes, orders, etc. |
| `products` | `ProductsApi` | Product catalog management |
| `receipts` | `ReceiptsApi` | Receipt management |
| `receivedDocuments` | `ReceivedDocumentsApi` | Received invoices/documents |
| `suppliers` | `SuppliersApi` | Supplier management |
| `taxes` | `TaxesApi` | Tax rates & settings |
| `user` | `UserApi` | User account information |
| `settings` | `SettingsApi` | Account settings & preferences |
| `archive` | `ArchiveApi` | Document archiving |
| `cashbook` | `CashbookApi` | Cash flow & transactions |

```php
// Core Entities
$clientsApi = FattureInCloud::createService('clients');
$suppliersApi = FattureInCloud::createService('suppliers');  
$productsApi = FattureInCloud::createService('products');
$issuedDocumentsApi = FattureInCloud::createService('issuedDocuments');
$receivedDocumentsApi = FattureInCloud::createService('receivedDocuments');
$receiptsApi = FattureInCloud::createService('receipts');

// Company & User Management
$companiesApi = FattureInCloud::createService('companies');
$userApi = FattureInCloud::createService('user');
$infoApi = FattureInCloud::createService('info');

// Settings & Configuration  
$settingsApi = FattureInCloud::createService('settings');
$taxesApi = FattureInCloud::createService('taxes');

// Additional Services
$archiveApi = FattureInCloud::createService('archive');
$cashbookApi = FattureInCloud::createService('cashbook');
```

### OAuth2 Authentication Flow

#### 1. Generate Authorization URL

```php
use FattureInCloud\OAuth2\Scope;

$scopes = [
    Scope::ENTITY_CLIENTS_READ,
    Scope::ENTITY_CLIENTS_ALL, 
    Scope::ENTITY_PRODUCTS_READ,
    Scope::ISSUED_DOCUMENTS_INVOICES_READ,
    Scope::ISSUED_DOCUMENTS_INVOICES_ALL
];

$authUrl = FattureInCloud::getAuthorizationUrl($scopes);
return redirect($authUrl);
```

#### 2. Handle Callback (Automatic Route)

The package automatically handles callbacks at `/fatture-in-cloud/callback`. You can customize the handling by creating your own controller:

```php
use Codeman\FattureInCloud\Facades\FattureInCloud;

class CustomCallbackController extends Controller
{
    public function handle(Request $request)
    {
        try {
            $result = FattureInCloud::handleCallback($request);
            
            if ($result['success']) {
                // Token is automatically stored and encrypted
                return redirect('/dashboard')->with('success', 'Connected to Fatture in Cloud!');
            }
            
            return redirect('/settings')->with('error', 'Authentication failed: ' . $result['error']);
        } catch (Exception $e) {
            return redirect('/settings')->with('error', 'Authentication failed: ' . $e->getMessage());
        }
    }
}
```

#### 3. Using API Services

```php
use FattureInCloud\Model\Client;
use FattureInCloud\Model\ClientType;
use FattureInCloud\Model\IssuedDocument;
use FattureInCloud\Model\IssuedDocumentType;

// Get user companies
$companiesApi = FattureInCloud::createService('companies');
$companies = $companiesApi->listUserCompanies();

// Get company ID for operations
$companyId = $companies->getData()[0]->getId();

// Create a new client
$clientsApi = FattureInCloud::createService('clients');
$newClient = new Client([
    'name' => 'New Client Ltd',
    'code' => 'CLIENT001',
    'type' => ClientType::COMPANY,
]);

$createdClient = $clientsApi->createClient($companyId, ['data' => $newClient]);

// List all products
$productsApi = FattureInCloud::createService('products');
$products = $productsApi->listProducts($companyId);

// Create an invoice
$issuedDocumentsApi = FattureInCloud::createService('issuedDocuments');
$invoice = new IssuedDocument([
    'type' => IssuedDocumentType::INVOICE,
    'entity' => $createdClient->getData(),
    'date' => date('Y-m-d'),
    'number' => 1,
    'numeration' => '/FAT',
]);

$createdInvoice = $issuedDocumentsApi->createIssuedDocument($companyId, ['data' => $invoice]);
```

### Token Management

```php
use Codeman\FattureInCloud\Facades\FattureInCloud;

// Check if user is authenticated
if (FattureInCloud::isAuthenticated()) {
    // User has valid tokens
    $userInfo = FattureInCloud::createService('user')->getUserInfo();
}

// Tokens are automatically refreshed when needed
// The package handles token refresh transparently

// Clear stored tokens (logout)
FattureInCloud::clearTokens();
```

### OAuth2 Scopes

The package supports all Fatture in Cloud OAuth2 scopes. Some common scopes include:

```php
use FattureInCloud\OAuth2\Scope;

// Entity Management
Scope::ENTITY_CLIENTS_READ      // Read customers
Scope::ENTITY_CLIENTS_ALL       // Full customer access
Scope::ENTITY_SUPPLIERS_READ    // Read suppliers
Scope::ENTITY_SUPPLIERS_ALL     // Full supplier access
Scope::ENTITY_PRODUCTS_READ     // Read products
Scope::ENTITY_PRODUCTS_ALL      // Full product access

// Document Types (Issued)
Scope::ISSUED_DOCUMENTS_INVOICES_READ         // Read invoices
Scope::ISSUED_DOCUMENTS_INVOICES_ALL          // Full invoice access
Scope::ISSUED_DOCUMENTS_CREDIT_NOTES_READ     // Read credit notes
Scope::ISSUED_DOCUMENTS_CREDIT_NOTES_ALL      // Full credit note access
Scope::ISSUED_DOCUMENTS_RECEIPTS_READ         // Read receipts
Scope::ISSUED_DOCUMENTS_RECEIPTS_ALL          // Full receipt access
Scope::ISSUED_DOCUMENTS_QUOTES_READ           // Read quotes
Scope::ISSUED_DOCUMENTS_QUOTES_ALL            // Full quote access

// Other Modules
Scope::RECEIVED_DOCUMENTS_READ  // Read received documents
Scope::RECEIVED_DOCUMENTS_ALL   // Full received documents access
Scope::TAXES_READ              // Read tax settings
Scope::TAXES_ALL               // Full tax settings access
Scope::ARCHIVE_READ            // Read archived documents
Scope::ARCHIVE_ALL             // Full archive access
Scope::CASHBOOK_READ           // Read cashbook
Scope::CASHBOOK_ALL            // Full cashbook access
Scope::SETTINGS_READ           // Read settings
Scope::SETTINGS_ALL            // Full settings access
```

### Manual Authentication

If you prefer to use a manually generated access token instead of OAuth2:

```env
FATTUREINCLOUD_ACCESS_TOKEN=your_access_token_here
```

When an access token is configured, OAuth2 settings are ignored and the SDK will use the token directly. This is useful for:

- Server-to-server integrations
- Background jobs and scheduled tasks
- Applications that don't need user-specific authentication
- Development and testing scenarios

## Architecture

The package follows clean architecture principles with dependency injection and contract-based design:

```
Codeman\FattureInCloud\
├── FattureInCloudSdk (main SDK class)
├── FattureInCloudServiceProvider (Laravel service provider)
├── Controllers\ (HTTP controllers)
│   └── OAuth2CallbackController - Single action invokable controller for OAuth2 callbacks
├── Contracts\ (Laravel-convention interfaces without "Interface" suffix)
│   ├── OAuth2Manager - OAuth2 authentication contract
│   ├── StateManager - CSRF state management contract
│   ├── TokenStorage - Token persistence contract
│   └── ApiServiceFactory - API service creation contract
├── Services\ (concrete implementations)
│   ├── OAuth2AuthorizationCodeManager - OAuth2 Authorization Code flow
│   ├── SessionStateManager - Laravel session-based state management
│   ├── CacheTokenStorage - Laravel cache-based token persistence
│   └── FattureInCloudApiServiceFactory - Fatture in Cloud API services factory
├── Exceptions\ (custom exceptions)
│   ├── OAuth2Exception - OAuth2-specific errors
│   └── UnsupportedServiceException - Service creation errors
└── Facades\ (Laravel facades)
    └── FattureInCloud - Main package facade
```

### Key Architectural Features

- **Clean Architecture**: Separation of concerns with clear boundaries
- **Dependency Injection**: All dependencies are injected and easily testable
- **Contract-Based Design**: Interface-driven development for flexibility
- **Factory Pattern**: Service creation through factory for consistency
- **Single Action Controllers**: OAuth2 callback controller follows Laravel's invokable controller pattern using `__invoke()`
- **Encrypted Token Storage**: Secure token persistence using Laravel's encryption
- **Automatic Route Registration**: OAuth2 callback route registered automatically
- **Laravel Integration**: Full integration with Laravel's service container and facades

## Development Commands

### Testing

Run the full test suite using Pest:

```bash
composer test
```

Run tests directly:

```bash
vendor/bin/pest
```

Run tests with coverage report:

```bash
vendor/bin/pest --coverage
```

Run tests in CI mode:

```bash
vendor/bin/pest --ci
```

### Code Quality

Run PHPStan static analysis:

```bash
composer analyse
```

Run PHPStan directly:

```bash
vendor/bin/phpstan analyse
```

Format code using Laravel Pint:

```bash
composer format
```

Format code directly:

```bash
vendor/bin/pint
```

### Package Development

Discover package (runs automatically after autoload dump):

```bash
composer prepare
```

## Security Features

- **Encrypted Token Storage**: All tokens are automatically encrypted before storage using Laravel's encryption
- **CSRF Protection**: Secure session-based state management for OAuth2 flows prevents CSRF attacks
- **Environment Configuration**: Credentials stored in environment variables prevent exposure in code
- **Automatic Token Refresh**: Transparent token refresh prevents expired token issues
- **Secure Callback Handling**: Built-in validation of OAuth2 callback parameters

## Publishing Configuration

When ready to publish package assets:

```bash
# Publish configuration file
php artisan vendor:publish --tag="laravel-fattureincloud-php-sdk-config"

# Publish migrations (if any)
php artisan vendor:publish --tag="laravel-fattureincloud-php-sdk-migrations"

# Publish views (if any)
php artisan vendor:publish --tag="laravel-fattureincloud-php-sdk-views"
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request. For major changes, please open an issue first to discuss what you would like to change.

### Development Workflow

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Run tests (`composer test`)
5. Run code quality checks (`composer analyse && composer format`)
6. Commit your changes (`git commit -m 'Add some amazing feature'`)
7. Push to the branch (`git push origin feature/amazing-feature`)
8. Open a Pull Request

## Security Vulnerabilities

If you discover a security vulnerability within this package, please send an email to the maintainers. All security vulnerabilities will be promptly addressed.

## Credits

- [Mattia Migliorini](https://github.com/deshack)
- [Visia Quantum](https://github.com/visiaquantum)
- All Contributors

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
