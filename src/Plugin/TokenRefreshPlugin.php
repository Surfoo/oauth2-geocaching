<?php

declare(strict_types=1);

namespace League\OAuth2\Client\Plugin;

use League\OAuth2\Client\Exception\RefreshTokenExpiredException;
use League\OAuth2\Client\Exception\TokenRefreshException;
use League\OAuth2\Client\Exception\TokenStorageException;
use League\OAuth2\Client\Provider\Exception\GeocachingIdentityProviderException;
use League\OAuth2\Client\Provider\Geocaching;
use League\OAuth2\Client\Token\AccessTokenInterface;
use League\OAuth2\Client\Token\TokenSet;
use League\OAuth2\Client\Token\TokenStorageInterface;
use Http\Client\Common\Plugin;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Plugin that automatically refreshes OAuth tokens when they expire.
 *
 * Uses the League OAuth2 Geocaching provider for token refresh operations.
 * Intercepts 401 responses, refreshes the access token using the refresh token,
 * updates the storage, and retries the original request.
 */
class TokenRefreshPlugin implements Plugin
{
    private const MAX_RETRY_ATTEMPTS     = 3;
    private const BACKOFF_MULTIPLIER     = 1.5;
    private const MAX_BACKOFF_MS         = 2000;
    private const BASE_BACKOFF_MS        = 100;

    public function __construct(
        private string $referenceCode,
        private TokenStorageInterface $storage,
        private Geocaching $oauthProvider,
        private ?LoggerInterface $logger = null,
        private int $maxRetryAttempts = self::MAX_RETRY_ATTEMPTS
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function handleRequest(RequestInterface $request, callable $next, callable $first): Promise
    {
        return $next($request)->then(
            function (ResponseInterface $response) use ($request, $first) {
                // If we get a 401, try to refresh the token and retry
                if ($response->getStatusCode() === 401) {
                    $this->logger->warning('[GEOCACHING] Received 401, attempting token refresh', [
                        'reference_code' => $this->referenceCode,
                        'request_uri' => (string) $request->getUri(),
                    ]);

                    // Unwrap the refreshed call to return a ResponseInterface (avoid Promise nesting)
                    return $this->refreshTokenAndRetry($request, $first)->wait();
                }

                return $response;
            },
            function (\Throwable $exception) {
                // Re-throw any other exceptions
                throw $exception;
            }
        );
    }

    /**
     * Refresh the token and retry the original request.
     */
    private function refreshTokenAndRetry(RequestInterface $request, callable $first): Promise
    {
        try {
            $newTokens  = $this->refreshAccessToken(forceRefresh: true);
            $newRequest = $this->updateRequestWithNewToken($request, $newTokens);

            $this->logger->notice('[GEOCACHING] Token refreshed successfully, retrying request', [
                'reference_code' => $this->referenceCode,
                'expires_at' => $newTokens->expiresAt->format('Y-m-d H:i:s'),
            ]);

            return $first($newRequest)->then(
                function (ResponseInterface $response) {
                    return $response;
                },
                function (\Throwable $exception) {
                    throw $exception;
                }
            );
        } catch (RefreshTokenExpiredException $e) {
            $this->logger->error('[GEOCACHING] Refresh token expired, user must re-authenticate', [
                'reference_code' => $this->referenceCode,
                'error'          => $e->getMessage(),
            ]);
            throw $e;
        } catch (TokenRefreshException $e) {
            $this->logger->error('[GEOCACHING] Failed to refresh token', [
                'reference_code' => $this->referenceCode,
                'error'          => $e->getMessage(),
                'response_data'  => $e->getResponseData(),
            ]);
            throw $e;
        }
    }

    /**
     * Refresh the access token with concurrency protection.
     */
    private function refreshAccessToken(bool $forceRefresh = false): TokenSet
    {
        // Try to acquire lock, with retries for concurrent requests
        for ($attempt = 0; $attempt < $this->maxRetryAttempts; $attempt++) {
            if ($this->storage->lockUser($this->referenceCode)) {
                try {
                    return $this->performTokenRefresh($forceRefresh);
                } finally {
                    $this->storage->unlockUser($this->referenceCode);
                }
            }

            // Another process is refreshing, wait and check if they succeeded
            if ($attempt < $this->maxRetryAttempts - 1) {
                usleep($this->calculateBackoff($attempt));

                // Check if the other process already refreshed the token
                $tokens = $this->storage->getTokens($this->referenceCode);
                if ($tokens && !$tokens->isExpired()) {
                    $this->logger->debug('[GEOCACHING] Token was refreshed by another process', [
                        'reference_code' => $this->referenceCode,
                    ]);
                    return $tokens;
                }
            }
        }

        throw new TokenStorageException("Could not acquire lock for user {$this->referenceCode} after {$this->maxRetryAttempts} attempts");
    }

    /**
     * Perform the actual token refresh (must be called within lock).
     */
    private function performTokenRefresh(bool $forceRefresh = false): TokenSet
    {
        // Get current tokens
        $currentTokens = $this->storage->getTokens($this->referenceCode);
        if (!$currentTokens) {
            throw new TokenStorageException("No tokens found for user {$this->referenceCode}");
        }

        // If we were called because of a 401, force a refresh even if the token
        // is not expired. Some APIs may revoke tokens early.
        if (!$forceRefresh && !$currentTokens->isExpired()) {
            $this->logger->debug('[GEOCACHING] Token is no longer expired, using existing token', [
                'reference_code' => $this->referenceCode,
            ]);
            return $currentTokens;
        }

        // Call OAuth refresh endpoint
        $oauthResponse = $this->callOAuthRefreshEndpoint($currentTokens->refreshToken);

        // Create new token set
        $newTokens = TokenSet::fromOAuthResponse($oauthResponse, $currentTokens->refreshToken);

        // Save to storage
        $this->storage->saveTokens($this->referenceCode, $newTokens);

        return $newTokens;
    }

    /**
     * Call the OAuth refresh endpoint using the Geocaching provider.
     */
    private function callOAuthRefreshEndpoint(string $refreshToken): array
    {
        try {
            $this->logger->debug('[GEOCACHING] Refreshing token using OAuth provider', [
                'reference_code' => $this->referenceCode,
                'provider_class' => $this->oauthProvider::class,
            ]);

            // Use the League OAuth2 provider to refresh the token
            $accessToken = $this->oauthProvider->getAccessToken('refresh_token', [
                'refresh_token' => $refreshToken,
            ]);

            $this->logger->debug('[GEOCACHING] Token refresh successful', [
                'reference_code'    => $this->referenceCode,
                'expires'           => $accessToken->getExpires(),
                'has_refresh_token' => !empty($accessToken->getRefreshToken()),
            ]);

            // Convert AccessToken to our expected array format
            return $this->convertAccessTokenToArray($accessToken, $refreshToken);
        } catch (GeocachingIdentityProviderException $e) {
            $responseBody = $e->getResponseBody();

            $this->logger->error('[GEOCACHING] OAuth provider exception during refresh', [
                'reference_code' => $this->referenceCode,
                'error'          => $e->getMessage(),
                'response_body'  => $responseBody,
            ]);

            // Map provider exceptions to our exceptions
            $errorData = json_decode((string) $responseBody, true) ?? [];

            // Check for expired refresh token errors
            if (isset($errorData['error'])) {
                $expiredErrors = ['invalid_grant', 'invalid_request', 'unauthorized_client'];
                if (in_array($errorData['error'], $expiredErrors, true)) {
                    throw new RefreshTokenExpiredException(
                        $errorData['error_description'] ?? 'Refresh token is invalid or expired'
                    );
                }
            }

            throw new TokenRefreshException(
                'OAuth provider error: ' . $e->getMessage(),
                $e->getCode(),
                $e,
                $errorData
            );
        } catch (\Exception $e) {
            $this->logger->error('[GEOCACHING] Unexpected error during token refresh', [
                'reference_code'  => $this->referenceCode,
                'error'           => $e->getMessage(),
                'exception_class' => $e::class,
            ]);

            throw new TokenRefreshException(
                'Unexpected error during token refresh: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Convert League OAuth2 AccessToken to our expected array format.
     */
    private function convertAccessTokenToArray(AccessTokenInterface $accessToken, string $originalRefreshToken): array
    {
        return [
            'access_token'  => $accessToken->getToken(),
            'refresh_token' => $accessToken->getRefreshToken() ?: $originalRefreshToken,
            'expires_in'    => $accessToken->getExpires() ? ($accessToken->getExpires() - time()) : 3600,
            'token_type'    => 'Bearer',
            'scope'         => null,
        ];
    }

    /**
     * Update request with new access token.
     */
    private function updateRequestWithNewToken(RequestInterface $request, TokenSet $tokens): RequestInterface
    {
        return $request->withHeader('Authorization', $tokens->getAuthorizationHeader());
    }

    /**
     * Calculate progressive backoff delay in microseconds.
     */
    private function calculateBackoff(int $attempt): int
    {
        $backoffMs = min(
            self::BASE_BACKOFF_MS * pow(self::BACKOFF_MULTIPLIER, $attempt),
            self::MAX_BACKOFF_MS
        );

        return (int)($backoffMs * 1000); // Convert to microseconds
    }
}
