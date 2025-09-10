<?php

namespace Codeman\FattureInCloud\Controllers;

use Codeman\FattureInCloud\Contracts\OAuth2Manager as OAuth2ManagerContract;
use Codeman\FattureInCloud\Contracts\TokenStorage as TokenStorageContract;
use Codeman\FattureInCloud\Exceptions\OAuth2Exception;
use Codeman\FattureInCloud\Services\OAuth2ErrorHandler;
use FattureInCloud\OAuth2\OAuth2TokenResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class OAuth2CallbackController extends Controller
{
    public function __construct(
        private OAuth2ManagerContract $oauth2Manager,
        private TokenStorageContract $tokenStorage,
        private OAuth2ErrorHandler $errorHandler
    ) {}

    public function __invoke(Request $request): Response
    {
        // Check for OAuth2 error response (user denial or other errors)
        if ($request->has('error')) {
            return $this->errorHandler->handleCallbackError($request);
        }

        // Validate required parameters
        if (! $request->has(['code', 'state'])) {
            $exception = OAuth2Exception::invalidRequest('Missing required parameters: code and state are required');

            return $this->errorHandler->createErrorResponse($exception);
        }

        // Process token exchange with comprehensive exception handling
        return $this->processTokenExchange($request);
    }

    private function processTokenExchange(Request $request): Response
    {
        try {
            // Exchange authorization code for access token
            $tokenResponse = $this->oauth2Manager->fetchToken(
                $request->get('code'),
                $request->get('state')
            );

            // Store the tokens
            $this->storeTokens($tokenResponse);

            return $this->errorHandler->createSuccessResponse([
                'token_type' => $tokenResponse->getTokenType(),
                'expires_in' => $tokenResponse->getExpiresIn(),
                'has_refresh_token' => ! empty($tokenResponse->getRefreshToken()),
            ], 'OAuth2 authorization completed successfully');

        } catch (\Exception $e) {
            return $this->errorHandler->handleException($e);
        }
    }

    private function storeTokens(OAuth2TokenResponse $tokenResponse): void
    {
        $this->tokenStorage->store('default', $tokenResponse);
    }
}
