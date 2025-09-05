# Laravel Wrapper for Fatture in Cloud SDK

[![Latest Version on Packagist](https://img.shields.io/packagist/v/codeman/laravel-fattureincloud-php-sdk.svg?style=flat-square)](https://packagist.org/packages/codeman/laravel-fattureincloud-php-sdk)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/visiaquantum/laravel-fattureincloud-php-sdk/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/visiaquantum/laravel-fattureincloud-php-sdk/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/visiaquantum/laravel-fattureincloud-php-sdk/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/visiaquantum/laravel-fattureincloud-php-sdk/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/codeman/laravel-fattureincloud-php-sdk.svg?style=flat-square)](https://packagist.org/packages/codeman/laravel-fattureincloud-php-sdk)

This package provides a simple, expressive, and robust wrapper around the official Fatture in Cloud PHP SDK. Its primary purpose is to abstract the complexity of the base SDK, offering a Laravel-native developer experience for interacting with the Fatture in Cloud API.

## Installation

You can install the package via composer:

```bash
composer require codeman/laravel-fattureincloud-php-sdk
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="laravel-fattureincloud-php-sdk-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-fattureincloud-php-sdk-config"
```

This is the contents of the published config file:

```php
return [
];
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="laravel-fattureincloud-php-sdk-views"
```

## Usage

```php
$laravelFattureInCloudPhpSdk = new Codeman\LaravelFattureInCloudPhpSdk();
echo $laravelFattureInCloudPhpSdk->echoPhrase('Hello, Codeman!');
```

## Testing

```bash
composer test
```

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
