<?php

namespace Codeman\FattureInCloud\Controllers;

use Codeman\FattureInCloud\Contracts\OAuth2Manager as OAuth2ManagerContract;
use Codeman\FattureInCloud\Contracts\TokenStorage as TokenStorageContract;
use Codeman\FattureInCloud\Exceptions\OAuth2Exception;
use FattureInCloud\OAuth2\OAuth2TokenResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class OAuth2CallbackController extends Controller
{
    public function __construct(
        private OAuth2ManagerContract $oauth2Manager,
        private TokenStorageContract $tokenStorage
    ) {}

    public function __invoke(Request $request): Response
    {
        // Check for OAuth2 error response (user denial or other errors)
        if ($request->has('error')) {
            return $this->handleOAuth2Error($request);
        }

        // Validate required parameters
        if (! $request->has(['code', 'state'])) {
            return $this->createErrorResponse(
                'Missing required parameters: code and state are required',
                400
            );
        }

        // Process token exchange with consolidated exception handling
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

            return $this->createSuccessResponse($tokenResponse);
        } catch (\Exception $e) {
            return $this->handleTokenExchangeException($e);
        }
    }

    private function handleTokenExchangeException(\Exception $e): Response
    {
        [$message, $statusCode] = match (true) {
            $e instanceof OAuth2Exception => ["OAuth2 error: {$e->getMessage()}", 400],
            $e instanceof \InvalidArgumentException => ["Invalid state parameter: {$e->getMessage()}", 400],
            $e instanceof \LogicException => ["Configuration error: {$e->getMessage()}", 500],
            default => ['An unexpected error occurred during token exchange', 500]
        };

        return $this->createErrorResponse($message, $statusCode);
    }

    private function handleOAuth2Error(Request $request): Response
    {
        $error = $request->get('error');
        $errorDescription = $request->get('error_description', 'No description provided');

        $message = match ($error) {
            'access_denied' => 'Authorization was denied by the user',
            'invalid_request' => 'The request is missing a required parameter or is otherwise malformed',
            'unauthorized_client' => 'The client is not authorized to request an authorization code',
            'unsupported_response_type' => 'The authorization server does not support the response type',
            'invalid_scope' => 'The requested scope is invalid, unknown, or malformed',
            'server_error' => 'The authorization server encountered an unexpected condition',
            'temporarily_unavailable' => 'The authorization server is temporarily overloaded or under maintenance',
            default => "OAuth2 error: {$error}"
        };

        return $this->createErrorResponse(
            "{$message}. Description: {$errorDescription}",
            400
        );
    }

    private function storeTokens(OAuth2TokenResponse $tokenResponse): void
    {
        $this->tokenStorage->store('default', $tokenResponse);
    }

    private function createSuccessResponse(OAuth2TokenResponse $tokenResponse): Response
    {
        $content = json_encode([
            'status' => 'success',
            'message' => 'OAuth2 authorization completed successfully',
            'data' => [
                'token_type' => $tokenResponse->getTokenType(),
                'expires_in' => $tokenResponse->getExpiresIn(),
                'has_refresh_token' => ! empty($tokenResponse->getRefreshToken()),
            ],
        ]);

        return new Response($content, 200, ['Content-Type' => 'application/json']);
    }

    private function createErrorResponse(string $message, int $statusCode): Response
    {
        $content = json_encode([
            'status' => 'error',
            'message' => $message,
        ]);

        return new Response($content, $statusCode, ['Content-Type' => 'application/json']);
    }
}
