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
     * OAuth2 Redirect URL
     *
     * The URL where users will be redirected after authorizing your application.
     * This must match exactly with the redirect URL configured in your
     * Fatture in Cloud OAuth2 application.
     *
     * If not set, it will default to your Laravel application URL followed
     * by the callback route path defined by this package.
     *
     * @see https://developers.fattureincloud.it/docs/authentication/oauth2-code-flow
     */
    'redirect_url' => env('FATTUREINCLOUD_REDIRECT_URL', config('app.url').'/fattureincloud/callback'),

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
