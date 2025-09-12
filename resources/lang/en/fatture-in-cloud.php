<?php

return [
    'oauth2' => [
        'authorization' => [
            'access_denied' => 'Authorization was cancelled. Please try again if you want to connect your account.',
            'invalid_request' => 'There was a problem with the authorization request. Please contact support.',
            'unauthorized_client' => 'This application is not authorized to access your account.',
            'unsupported_response_type' => 'Authorization method not supported. Please contact support.',
            'invalid_scope' => 'The requested permissions are not available.',
            'server_error' => 'A temporary server error occurred. Please try again in a moment.',
            'temporarily_unavailable' => 'The service is temporarily unavailable. Please try again later.',
            'default' => 'An authorization error occurred. Please try again or contact support.',
        ],
        'token_exchange' => [
            'invalid_code' => 'The authorization code has expired or is invalid. Please restart the authorization process.',
            'invalid_client_credentials' => 'Application credentials are invalid. Please contact support.',
            'network_failure' => 'Network connection failed. Please check your connection and try again.',
            'default' => 'A token exchange error occurred. Please try again or contact support.',
        ],
        'token_refresh' => [
            'invalid_refresh_token' => 'Your session has expired. Please log in again.',
            'client_authentication_failed' => 'Authentication failed. Please try logging in again.',
            'token_revoked' => 'Your access has been revoked. Please log in again.',
            'default' => 'A token refresh error occurred. Please log in again.',
        ],
        'configuration' => [
            'missing_configuration' => 'Application configuration is incomplete. Please contact support.',
            'invalid_redirect_url' => 'Invalid redirect URL configuration. Please contact support.',
            'malformed_configuration' => 'Application configuration error. Please contact support.',
            'default' => 'A configuration error occurred. Please contact support.',
        ],
    ],
];
