# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel package that provides a wrapper around the official Fatture in Cloud PHP SDK. The package abstracts the complexity of the base SDK and offers a Laravel-native developer experience for interacting with the Fatture in Cloud API.

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

## Architecture

### Package Structure
- **Service Provider**: `LaravelFattureInCloudPhpSdkServiceProvider` - Main entry point using Spatie's Package Tools
- **Main Class**: `LaravelFattureInCloudPhpSdk` - Core package functionality (currently empty - to be implemented)
- **Facade**: `LaravelFattureInCloudPhpSdk` - Laravel facade for easy access
- **Command**: `LaravelFattureInCloudPhpSdkCommand` - Artisan command placeholder

### Testing Setup
- Uses Orchestra Testbench for Laravel package testing
- Pest PHP as the testing framework
- TestCase configured with proper service provider registration
- Factory namespace guessing configured for database factories

### Configuration
- Package name: `laravel-fattureincloud-php-sdk`
- Namespace: `codeman\LaravelFattureInCloudPhpSdk`
- Supports config file, views, migrations, and commands
- Empty config file ready for implementation

### Dependencies
- PHP 8.4+ required
- Laravel 11.x/12.x compatibility
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