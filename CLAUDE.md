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
