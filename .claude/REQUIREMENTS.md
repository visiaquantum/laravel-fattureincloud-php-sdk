# Laravel Wrapper for Fatture in Cloud SDK

## 1\. Project Overview & Goals

**Purpose**

This package will provide a simple, expressive, and robust wrapper around the official
`fattureincloud/fattureincloud-php-sdk`. Its primary purpose is to abstract the complexity of the base SDK, offering a *
*"Laravel-native" developer experience** for interacting with the Fatture in Cloud API.

**Primary Goals**

- **Simplify Integration:** Drastically reduce the setup time and boilerplate code required to use the Fatture in Cloud
  API within a Laravel application.
- **Enhance Developer Experience (DX):** Offer a clean, fluent, and intuitive API through a Laravel Facade, making API
  interactions feel like a natural part of the framework.
- **Promote Best Practices:** Encourage secure API key management through standard Laravel configuration and .env files.
- **Ensure Maintainability:** Build the package using `spatie/laravel-package-tools` to standardize its structure and
  simplify future maintenance.

**Target Audience**

The package is intended for **Laravel developers of all skill levels**, from beginners needing a straightforward
integration path to experts who value clean architecture and reduced boilerplate.

## 2\. Core Functional Requirements

**SDK Wrapping & Service Container**

The core functionality of the wrapper will be managed by a central service class (e.g., `FattureInCloudService`).

- This service class will be responsible for instantiating the underlying SDK `ApiClient` and all the necessary API
  services (e.g., `IssuedDocumentsApi`, `ClientsApi`, `ProductsApi`).
- The service class must be registered as a **singleton** in the Laravel service container. This ensures that the SDK
  client is instantiated only once per request lifecycle, using the access token from the configuration.

**Service Provider**

A `FattureInCloudServiceProvider` must be created using the `spatie/laravel-package-tools`. Its responsibilities are:

- **Binding:** Register the `FattureInCloudService` class as a singleton in the container.
- **Configuration:** Register the package's configuration file (`fattureincloud.php`) so it can be published and used by
  the application.

**Facade**

A `FattureInCloud` Facade must be created to provide a simple, static-like interface for developers.

- The Facade must resolve to the `FattureInCloudService` instance from the service container.
- It should expose methods that return the pre-configured API service instances from the SDK, allowing for a fluent and
  chainable syntax.
    - *Example:* `FattureInCloud::issuedDocuments()->listIssuedDocuments(...)`

**Configuration**

A `config/fattureincloud.php` configuration file is required.

- It must contain an `access_token` key.
- The value for this key **must** be sourced from an environment variable, `env('FATTUREINCLOUD_ACCESS_TOKEN', null)`.
- The file should include clear comments guiding the user on where to find their API token and how to set it in their
  `.env` file.

## 3\. Technical & Architectural Requirements

**Scaffolding**

The entire package structure, including the service provider, must be generated and managed using *
*`spatie/laravel-package-tools`**. This is a non-negotiable requirement to ensure standardization.

**Compatibility**

The package must be compatible with the following versions:

- **Laravel:** `^11.0||^12.0`
- **PHP:** `^8.2`

**Dependencies**

The `composer.json` file must correctly list:

- `fattureincloud/fattureincloud-php-sdk` in the `require` section.
- `spatie/laravel-package-tools` in the `require` section.

**Coding Standards & Autoloading**

- **Standard:** The codebase must adhere strictly to the **PSR-12** coding standard.
- **Formatting:** **Laravel Pint** must be configured and used for automated code formatting. A `pint.json` file should
  be included in the repository.
- **Autoloading:** The `composer.json` file must be configured for **PSR-4 autoloading**, mapping the package's
  namespace (e.g., `YourVendor\\FattureInCloud\\`) to the `src/` directory.

## 4\. Developer Experience (DX) & Usability

**Installation**

Installation must be a single, standard Composer command:

``` bash
composer require your-vendor/laravel-fattureincloud

```

**Configuration Publishing**

The package must support the standard `vendor:publish` Artisan command to allow users to publish the
`fattureincloud.php` configuration file to their application's `config` directory.

``` bash
php artisan vendor:publish --provider="YourVendor\\FattureInCloud\\FattureInCloudServiceProvider"

```

**Usage Example ("Hello World")**

The core DX goal is simplicity. The following code demonstrates the target developer experience for listing the 5 most
recent invoices for a given company. This should be the primary example in the documentation.

``` php
use Illuminate\Support\Facades\Log;
use YourVendor\FattureInCloud\Facades\FattureInCloud;
use FattureInCloud\Sdk\ApiException;

// Get the company ID from your application's logic
$companyId = 12345;

try {
    // Use the Facade to access the IssuedDocumentsApi and call its method
    $apiResponse = FattureInCloud::issuedDocuments()->listIssuedDocuments(
        $companyId,
        'invoice', // type
        null,      // year
        null,      // sort
        1,         // page
        5          // per_page
    );

    $invoices = $apiResponse->getData();

    // Now you can work with the list of invoices
    foreach ($invoices as $invoice) {
        echo "Invoice #: " . $invoice->getNumber();
    }

} catch (ApiException $e) {
    Log::error("Fatture in Cloud API exception: " . $e->getMessage());
}

```

## 5\. Testing & Quality Assurance

**Test Suite**

- A comprehensive test suite must be developed using **Pest**.
- **Coverage:** Tests must cover:
    - Correct registration and resolution of the service provider and singleton.
    - Facade methods correctly resolve and return the appropriate SDK API classes.
    - Configuration values are loaded and used correctly.
- **API Mocking:** All tests involving API calls must **mock the SDK's ApiClient** to prevent actual HTTP requests and
  ensure fast, reliable test runs.

**CI/CD**

- A **GitHub Actions** workflow must be configured in the repository.
- The workflow must be triggered on every push and pull\_request to the main (or master) branch.
- **Jobs:** The workflow must include jobs to:
    1. Install dependencies (`composer install`).
    2. Run code style checks (`vendor/bin/pint --test`).
    3. Execute the full Pest test suite (`vendor/bin/pest`).

## 6\. Documentation & Final Deliverables

**README.md**

A high-quality, comprehensive `README.md` file is a critical deliverable. It must contain the following sections:

- Project Title and a brief one-line description.
- Badges for CI status, latest stable version, and license.
- **Installation:** Clear, copy-pasteable instructions.
- **Configuration:** Steps for publishing the config file and setting the `.env` variable.
- **Usage:**
    - The basic "Hello World" example (listing invoices).
    - At least one other advanced example (e.g., creating a new client).
- **Testing:** Instructions on how to run the package's test suite locally.
- **Changelog:** A link to the `CHANGELOG.md` file.
- **Contributing:** Brief guidelines and a link to `CONTRIBUTING.md` if necessary.
- **License:** Mention the license type (MIT).

**CHANGELOG.md**

A `CHANGELOG.md` file must be created and maintained for all subsequent releases. It must follow the conventions
outlined at [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

**License**

The package must be released under the permissive **MIT License**. A `LICENSE.md` file containing the full license text
must be included in the repository root.
