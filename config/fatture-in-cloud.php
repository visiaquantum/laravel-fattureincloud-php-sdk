<?php

/**
 * Configuration for the Laravel Fatture in Cloud PHP SDK
 *
 * This package provides a Laravel wrapper for the official Fatture in Cloud PHP SDK.
 * For detailed authentication documentation, see:
 * https://developers.fattureincloud.it/docs/authentication/
 */
return [
    /*
    |--------------------------------------------------------------------------
    | OAuth2 Authentication Configuration
    |--------------------------------------------------------------------------
    |
    | Configure your Fatture in Cloud OAuth2 application credentials here.
    | You can create an OAuth2 application in your Fatture in Cloud developer
    | console: https://developers.fattureincloud.it/
    |
    */

    /**
     * OAuth2 Client ID
     *
     * Your application's client ID from the Fatture in Cloud developer console.
     * This is optional if you're using manual authentication with an access_token.
     *
     * @see https://developers.fattureincloud.it/docs/authentication/oauth2-code-flow
     */
    'client_id' => env('FATTUREINCLOUD_CLIENT_ID'),

    /**
     * OAuth2 Client Secret
     *
     * Your application's client secret from the Fatture in Cloud developer console.
     * This is optional if you're using manual authentication with an access_token.
     *
     * ⚠️  SECURITY WARNING: Never commit your client secret to version control.
     *    Always set this value via the FATTUREINCLOUD_CLIENT_SECRET environment variable.
     *
     * @see https://developers.fattureincloud.it/docs/authentication/oauth2-code-flow
     */
    'client_secret' => env('FATTUREINCLOUD_CLIENT_SECRET'),

    /**
     * OAuth2 Redirect URL (Optional Override)
     *
     * AUTOMATIC GENERATION: The package automatically generates the redirect URL using
     * Laravel's route() helper for the named route 'fatture-in-cloud.callback'.
     * Laravel's route() helper uses your APP_URL configuration internally.
     *
     * MANUAL OVERRIDE: Only set this environment variable if you need to override
     * the automatically generated URL for specific deployment scenarios (e.g., load
     * balancers, CDNs, or custom domain configurations).
     *
     * The URL where users will be redirected after authorizing your application.
     * This must match exactly with the redirect URL configured in your
     * Fatture in Cloud OAuth2 application.
     *
     * Examples of when manual override might be needed:
     * - Behind a reverse proxy: https://api.yourdomain.com/fatture-in-cloud/callback
     * - Custom subdomain: https://billing.yourdomain.com/fatture-in-cloud/callback
     * - Different port: https://yourdomain.com:8080/fatture-in-cloud/callback
     * - Load balancer scenarios with different external/internal URLs
     *
     * SETUP REQUIREMENTS for automatic generation:
     * - Ensure APP_URL is configured in your .env file
     * - The callback route is automatically registered by this package
     *
     * @see https://developers.fattureincloud.it/docs/authentication/oauth2-code-flow
     */
    'redirect_url' => env('FATTUREINCLOUD_REDIRECT_URL'),

    /**
     * Manual Authentication Access Token
     *
     * If you prefer manual authentication over OAuth2, you can set your access token here.
     * When this is set, it takes precedence over the OAuth2 configuration above.
     *
     * To obtain an access token manually, follow the manual authentication guide:
     * https://developers.fattureincloud.it/docs/authentication/manual-authentication
     *
     * ⚠️  SECURITY WARNING: Never commit your access token to version control.
     *    Always set this value via the FATTUREINCLOUD_ACCESS_TOKEN environment variable.
     */
    'access_token' => env('FATTUREINCLOUD_ACCESS_TOKEN'),
];
