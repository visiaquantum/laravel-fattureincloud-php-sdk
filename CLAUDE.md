# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel package that provides a wrapper around the official Fatture in Cloud PHP SDK. The package abstracts the complexity of the base SDK and offers a Laravel-native developer experience for interacting with the Fatture in Cloud API.

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
- **Service Provider**: `LaravelFattureInCloudPhpSdkServiceProvider` - Main entry point using Spatie's Package Tools
- **Main Class**: `LaravelFattureInCloudPhpSdk` - Core package functionality (currently empty - to be implemented)
- **Facade**: `FattureInCloud` - Laravel facade for easy access (renamed from `LaravelFattureInCloudPhpSdk`)
- **Command**: `FattureInCloudCommand` - Artisan command (renamed from `LaravelFattureInCloudPhpSdkCommand`)

### Testing Setup
- Uses Orchestra Testbench for Laravel package testing
- Pest PHP as the testing framework
- TestCase configured with proper service provider registration
- Factory namespace guessing configured for database factories

### Configuration
- Package name: `laravel-fattureincloud-php-sdk`
- Namespace: `codeman\LaravelFattureInCloudPhpSdk`
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
