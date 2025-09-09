<?php

use Codeman\FattureInCloud\Controllers\OAuth2CallbackController;
use Illuminate\Support\Facades\Route;

Route::get('/fatture-in-cloud/callback', [OAuth2CallbackController::class, 'handleCallback'])
    ->name('fatture-in-cloud.callback');
