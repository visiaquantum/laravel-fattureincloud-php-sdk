# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel package that provides a wrapper around the official Fatture in Cloud PHP SDK. The package abstracts the complexity of the base SDK and offers a Laravel-native developer experience for interacting with the Fatture in Cloud API.

The package follows Laravel ecosystem conventions with a clean, simplified architecture that provides OAuth2 authentication flows, token management, and service abstraction for seamless API integration.

- GitHub repository URL is https://github.com/visiaquantum/laravel-fattureincloud-php-sdk. owner: "visiaquantum", repo: "laravel-fattureincloud-php-sdk"

## Development Commands

### Testing
- `composer test` - Run the full test suite using Pest
- `vendor/bin/pest` - Run tests directly
- `vendor/bin/pest --coverage` - Run tests with coverage report
- `vendor/bin/pest --ci` - Run tests in CI mode

### Code Quality
- `composer analyse` - Run PHPStan static analysis
- `vendor/bin/phpstan analyse` - Run PHPStan directly
- `composer format` - Format code using Laravel Pint
- `vendor/bin/pint` - Format code directly

### Package Development
- `composer prepare` - Discover package (runs automatically after autoload dump)

### Artisan Commands
- `php artisan fatture-in-cloud` - Main package command (renamed from `laravel-fattureincloud-php-sdk`)

## Architecture

### Package Structure
- **Service Provider**: `FattureInCloudServiceProvider` - Main entry point using Spatie's Package Tools
- **Main Class**: `FattureInCloudSdk` - Core package functionality with OAuth2 integration
- **Facade**: `FattureInCloud` - Laravel facade for easy access
- **Command**: `FattureInCloudCommand` - Artisan command for package interaction

### Clean Architecture Components
```
Codeman\FattureInCloud\
├── FattureInCloudSdk (main SDK class)
├── FattureInCloudServiceProvider (Laravel service provider)
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

### Testing Setup
- Uses Orchestra Testbench for Laravel package testing
- Pest PHP as the testing framework
- TestCase configured with proper service provider registration
- Factory namespace guessing configured for database factories

### Configuration
- Package name: `laravel-fattureincloud-php-sdk`
- **Current Namespace**: `Codeman\FattureInCloud` (clean, Laravel-convention naming)
- Supports config file, views, migrations, and commands
- **OAuth2 Configuration System**: Complete configuration for authentication flows
- **Environment Variables**: 
  - `FATTUREINCLOUD_CLIENT_ID` - OAuth2 client ID (optional if using access token)
  - `FATTUREINCLOUD_CLIENT_SECRET` - OAuth2 client secret (optional if using access token)
  - `FATTUREINCLOUD_REDIRECT_URL` - OAuth2 redirect URL (defaults to app URL + callback path)
  - `FATTUREINCLOUD_ACCESS_TOKEN` - Manual authentication token (takes precedence when set)

### Dependencies
- PHP 8.4+ required
- Laravel 11.x/12.x compatibility
- **Fatture in Cloud PHP SDK**: `fattureincloud/fattureincloud-php-sdk: ^2.1` - Official SDK integration
- Uses Spatie's Laravel Package Tools for package structure
- Development tools: Pest, PHPStan, Laravel Pint

## CI/CD Workflows

The project uses GitHub Actions with three main workflows:
- **Tests**: Runs on multiple PHP/Laravel versions (PHP 8.3-8.4, Laravel 11-12) across Ubuntu/Windows
- **Code Style**: Automatically fixes PHP code style issues using Laravel Pint
- **Static Analysis**: Runs PHPStan analysis

## Upstream SDK Documentation

This section documents the official Fatture in Cloud PHP SDK (`fattureincloud/fattureincloud-php-sdk`) that our Laravel wrapper depends on.

### Version Information
- **Current Constraint**: `^2.1` (in composer.json)
- **Installed Version**: `2.1.3` (from vendor/fattureincloud/fattureincloud-php-sdk/sdk-version.yaml)
- **OpenAPI Version**: `2.1.5` (from source files)
- **Generator**: OpenAPI Generator v7.12.0
- **Base API URL**: `https://api-v2.fattureincloud.it`

### SDK Architecture Overview

The SDK is structured around several core components:

```
FattureInCloud\
├── Api\                        # API Service Classes (17 services)
├── Model\                      # Data Models & Entities (200+ classes)  
├── OAuth2\                     # OAuth2 Authentication Components
├── Filter\                     # Query Filter Classes
├── Configuration.php           # SDK Configuration Management
├── HeaderSelector.php          # HTTP Header Management
├── ObjectSerializer.php        # JSON Serialization/Deserialization
└── ApiException.php           # Exception Handling
```

### Core Configuration Class

**`FattureInCloud\Configuration`** - Central configuration management
- **Purpose**: SDK-wide configuration including authentication, HTTP settings
- **Key Properties**:
  - `$accessToken`: OAuth2 Bearer token for authentication
  - `$host`: API base URL (default: https://api-v2.fattureincloud.it)
  - `$userAgent`: HTTP user agent (FattureInCloud/2.1.3/PHP-SDK)
  - `$apiKeys[]`: API key storage for different auth schemes
  - `$debug`: Debug mode toggle
- **Usage in Wrapper**: Used to configure all API service instances with authentication tokens

### OAuth2 Authentication System

**`FattureInCloud\OAuth2\OAuth2AuthorizationCode\OAuth2AuthorizationCodeManager`** - Authorization Code Flow
- **Purpose**: Handles OAuth2 Authorization Code flow for secure API access
- **Key Methods**:
  - `getAuthorizationUrl(array $scopes, string $state): string` - Generate OAuth2 authorization URL
  - `getParamsFromUrl(string $url): OAuth2AuthorizationCodeParams` - Parse callback URL parameters  
  - `fetchToken(string $code): OAuth2TokenResponse|OAuth2Error` - Exchange code for access token
  - `refreshToken(string $refreshToken): OAuth2TokenResponse|OAuth2Error` - Refresh expired tokens
- **Constructor**: `($clientId, $clientSecret, $redirectUri, $baseUri?, $httpClient?)`
- **Usage in Wrapper**: Instantiated by our `OAuth2AuthorizationCodeManager` service

**`FattureInCloud\OAuth2\OAuth2TokenResponse`** - Token Response Model
- **Properties**: `tokenType`, `accessToken`, `refreshToken`, `expiresIn`
- **Methods**: `toJson()`, `fromJson(string $json)` for serialization
- **Usage in Wrapper**: Returned by OAuth2 flows, stored by our token storage system

**`FattureInCloud\OAuth2\OAuth2Error`** - Error Response Model  
- **Properties**: `code`, `error`, `errorDescription`
- **Methods**: `toJson()`, `fromJson(string $json)` for serialization
- **Usage in Wrapper**: Handled by our OAuth2Exception system

### Available API Services

Our Laravel wrapper provides access to 13 core API services through the factory pattern:

| Service Key | API Class | Purpose |
|-------------|-----------|---------|
| `clients` | `FattureInCloud\Api\ClientsApi` | Customer/client management |
| `companies` | `FattureInCloud\Api\CompaniesApi` | Company information & settings |
| `info` | `FattureInCloud\Api\InfoApi` | System information & metadata |
| `issuedDocuments` | `FattureInCloud\Api\IssuedDocumentsApi` | Invoices, quotes, orders, etc. |
| `products` | `FattureInCloud\Api\ProductsApi` | Product catalog management |
| `receipts` | `FattureInCloud\Api\ReceiptsApi` | Receipt management |
| `receivedDocuments` | `FattureInCloud\Api\ReceivedDocumentsApi` | Received invoices/documents |
| `suppliers` | `FattureInCloud\Api\SuppliersApi` | Supplier management |
| `taxes` | `FattureInCloud\Api\TaxesApi` | Tax rates & settings |
| `user` | `FattureInCloud\Api\UserApi` | User account information |
| `settings` | `FattureInCloud\Api\SettingsApi` | Account settings & preferences |
| `archiveDocuments` | `FattureInCloud\Api\ArchiveApi` | Document archiving |
| `cashbook` | `FattureInCloud\Api\CashbookApi` | Cash flow & transactions |
| `priceLists` | `FattureInCloud\Api\PriceListsApi` | Price list management |

**Additional Available Services** (not yet included in wrapper):
- `FattureInCloud\Api\EmailsApi` - Email management
- `FattureInCloud\Api\IssuedEInvoicesApi` - Electronic invoice handling  
- `FattureInCloud\Api\WebhooksApi` - Webhook subscriptions

### OAuth2 Scopes & Permissions

**`FattureInCloud\OAuth2\Scope`** - Available permission scopes (35 total)

**Entity Management:**
- `ENTITY_CLIENTS_READ/ALL` - Customer registry access
- `ENTITY_SUPPLIERS_READ/ALL` - Supplier registry access
- `PRODUCTS_READ/ALL` - Product catalog access

**Document Types (Issued):**
- `ISSUED_DOCUMENTS_INVOICES_READ/ALL` - Invoices
- `ISSUED_DOCUMENTS_CREDIT_NOTES_READ/ALL` - Credit notes
- `ISSUED_DOCUMENTS_RECEIPTS_READ/ALL` - Receipts
- `ISSUED_DOCUMENTS_ORDERS_READ/ALL` - Orders
- `ISSUED_DOCUMENTS_QUOTES_READ/ALL` - Quotes
- `ISSUED_DOCUMENTS_PROFORMAS_READ/ALL` - Proforma invoices
- `ISSUED_DOCUMENTS_DELIVERY_NOTES_READ/ALL` - Delivery notes
- `ISSUED_DOCUMENTS_WORK_REPORTS_READ/ALL` - Work reports
- `ISSUED_DOCUMENTS_SUPPLIER_ORDERS_READ/ALL` - Supplier orders
- `ISSUED_DOCUMENTS_SELF_INVOICES_READ/ALL` - Self invoices

**Other Modules:**
- `RECEIVED_DOCUMENTS_READ/ALL` - Received documents
- `STOCK_READ/ALL` - Stock movements
- `RECEIPTS_READ/ALL` - Receipt handling
- `CALENDAR_READ/ALL` - Calendar integration
- `TAXES_READ/ALL` - Tax management
- `ARCHIVE_READ/ALL` - Document archiving
- `EMAILS_READ` - Email access (read-only)
- `CASHBOOK_READ/ALL` - Cashbook management
- `SETTINGS_READ/ALL` - Settings configuration
- `SITUATION_READ` - Company situation (read-only)

### Common Usage Patterns in Our Wrapper

1. **Configuration Setup:**
   ```php
   $config = new Configuration();
   $config->setAccessToken($token);
   ```

2. **API Service Creation:**
   ```php
   $companiesApi = new CompaniesApi($httpClient, $config, $headerSelector);
   ```

3. **OAuth2 Manager Initialization:**
   ```php
   $oauth2Manager = new OAuth2AuthorizationCodeManager(
       $clientId, $clientSecret, $redirectUri
   );
   ```

4. **Token Exchange:**
   ```php
   $result = $oauth2Manager->fetchToken($authorizationCode);
   if ($result instanceof OAuth2TokenResponse) {
       // Success - use $result->getAccessToken()
   } else {
       // Error - handle OAuth2Error
   }
   ```

### Model Structure

The SDK includes 200+ model classes in `FattureInCloud\Model\` namespace:
- **Core Entities**: `Company`, `Client`, `Supplier`, `Product`
- **Documents**: Various invoice, receipt, and document types
- **Responses**: API response wrapper classes
- **Requests**: API request parameter classes
- **Enums**: Type definitions and constants

### HTTP Layer

- **HTTP Client**: Uses Guzzle HTTP client
- **Authentication**: Bearer token via Authorization header
- **Serialization**: Automatic JSON serialization/deserialization
- **Error Handling**: `FattureInCloud\ApiException` for API errors

### Version Tracking & Updates

To track SDK updates:
1. **Check Constraint**: Review `composer.json` for version constraint (`^2.1`)
2. **Verify Installed**: Check `vendor/fattureincloud/fattureincloud-php-sdk/sdk-version.yaml`
3. **Update Process**: Run `composer update fattureincloud/fattureincloud-php-sdk`
4. **Breaking Changes**: Monitor SDK releases for API changes
5. **OpenAPI Spec**: Generated from OpenAPI 2.1.5 specification

### Integration Notes for Wrapper Development

- All API classes follow consistent constructor pattern: `($httpClient, $config, $headerSelector)`
- OAuth2 managers extend base `OAuth2Manager` class
- All models implement `ModelInterface`, `ArrayAccess`, and `JsonSerializable`
- Exception handling through `ApiException` class
- Consistent method naming and response patterns across all API services
- Full PSR-7 HTTP message compliance through Guzzle integration

## Current Functionality

### OAuth2 Authentication Flows
The package provides complete OAuth2 integration with:
- **Authorization Code Flow**: Full OAuth2 implementation with state management
- **Token Management**: Automatic token storage and retrieval using Laravel cache
- **Session State Management**: CSRF protection using Laravel sessions
- **Manual Token Authentication**: Direct API access using access tokens

### Service Factory Pattern
- **API Service Creation**: Factory pattern for creating Fatture in Cloud API services
- **Dependency Injection**: All services properly registered in Laravel container
- **Contract-Based Architecture**: Interface-driven design for testability and flexibility

### Usage Examples

```php
// Using the facade for OAuth2 flow
use Codeman\FattureInCloud\Facades\FattureInCloud;

// Start OAuth2 authorization
$authUrl = FattureInCloud::getAuthorizationUrl();
return redirect($authUrl);

// Handle callback
$token = FattureInCloud::handleCallback($request);

// Create API services
$companiesApi = FattureInCloud::createService('companies');
$customersApi = FattureInCloud::createService('customers');
```

## Package Publishing

When ready to publish configurations:
- Config: `php artisan vendor:publish --tag="laravel-fattureincloud-php-sdk-config"`
- Migrations: `php artisan vendor:publish --tag="laravel-fattureincloud-php-sdk-migrations"`
- Views: `php artisan vendor:publish --tag="laravel-fattureincloud-php-sdk-views"`

## Breaking Changes

### From PRs #9 and #10

**PR #9 - Core Architecture & Setup:**
- ✅ Facade renamed: `LaravelFattureInCloudPhpSdk` → `FattureInCloud`
- ✅ Command renamed: `LaravelFattureInCloudPhpSdkCommand` → `FattureInCloudCommand`
- ✅ Command signature changed: `laravel-fattureincloud-php-sdk` → `fatture-in-cloud`
- ✅ Added official Fatture in Cloud PHP SDK dependency (`fattureincloud/fattureincloud-php-sdk: ^2.1`)

**PR #10 - OAuth2 Configuration System:**
- ✅ Comprehensive OAuth2 configuration implemented
- ✅ Support for both OAuth2 flow and manual authentication via access token
- ✅ Environment variables for authentication credentials
- ✅ Smart defaults for redirect URLs
- ✅ Security warnings and documentation included

### Latest Major Refactoring - Clean Architecture Implementation

**BREAKING: Namespace Refactoring:**
- ✅ **Root Namespace**: `Codeman\LaravelFattureInCloudPhpSdk` → `Codeman\FattureInCloud`
- ✅ Simplified, Laravel ecosystem-compliant naming convention

**BREAKING: Class Name Simplifications:**
- ✅ **Main Class**: `LaravelFattureInCloudPhpSdk` → `FattureInCloudSdk`
- ✅ **Service Provider**: `LaravelFattureInCloudPhpSdkServiceProvider` → `FattureInCloudServiceProvider`
- ✅ Removed verbose prefixes for cleaner, more maintainable codebase

**Laravel Convention Improvements:**
- ✅ **Contract Naming**: Removed "Interface" suffix from contracts (e.g., `OAuth2ManagerInterface` → `OAuth2Manager`)
- ✅ **Descriptive Service Names**: 
  - `OAuth2Manager` implementation → `OAuth2AuthorizationCodeManager` (specific to Authorization Code flow)
  - Clear, descriptive naming for all service implementations
- ✅ **Exception Specificity**: Replaced generic `RuntimeException` with specific `OAuth2Exception`
- ✅ **Architecture Clean-up**: Complete alignment with Laravel ecosystem conventions

**Code Quality & Security:**
- ✅ **SonarQube Compliance**: All code quality issues resolved
- ✅ **Type Safety**: Proper exception handling and type declarations
- ✅ **Security**: Proper OAuth2 error handling and state management

**Impact:**
- This refactoring creates a clean, maintainable codebase that follows Laravel best practices
- All class names are now concise and follow Laravel ecosystem conventions
- The architecture is ready for production use with proper OAuth2 flows implemented
