<?php

declare(strict_types=1);

namespace League\OAuth2\Client\Test\Factory;

use League\OAuth2\Client\Token\TokenSet;
use DateTimeImmutable;

/**
 * Factory class to create TokenSet instances for testing purposes.
 */
class TokenSetTestFactory
{
    /**
     * Create an expired TokenSet for testing.
     */
    public static function createExpired(
        string $accessToken = 'expired-access-token',
        string $refreshToken = 'refresh-token'
    ): TokenSet {
        return new TokenSet(
            $accessToken,
            $refreshToken,
            new DateTimeImmutable('-1 hour'), // Expired 1 hour ago
            'Bearer'
        );
    }

    /**
     * Create a valid TokenSet that won't expire soon.
     */
    public static function createValid(
        string $accessToken = 'valid-access-token',
        string $refreshToken = 'refresh-token',
        int $expiresInSeconds = 3600
    ): TokenSet {
        return new TokenSet(
            $accessToken,
            $refreshToken,
            new DateTimeImmutable("+{$expiresInSeconds} seconds"),
            'Bearer'
        );
    }

    /**
     * Create a TokenSet that is near expiry (within buffer window).
     */
    public static function createNearExpiry(
        string $accessToken = 'near-expiry-access-token',
        string $refreshToken = 'refresh-token',
        int $expiresInSeconds = 30
    ): TokenSet {
        return new TokenSet(
            $accessToken,
            $refreshToken,
            new DateTimeImmutable("+{$expiresInSeconds} seconds"),
            'Bearer'
        );
    }

    /**
     * Create a TokenSet with custom scopes.
     */
    public static function createWithScopes(
        array $scopes,
        string $accessToken = 'scoped-access-token',
        string $refreshToken = 'refresh-token',
        int $expiresInSeconds = 3600
    ): TokenSet {
        return new TokenSet(
            $accessToken,
            $refreshToken,
            new DateTimeImmutable("+{$expiresInSeconds} seconds"),
            'Bearer',
            $scopes
        );
    }

    /**
     * Create a TokenSet from OAuth response data for testing.
     */
    public static function createFromOAuthResponse(
        array $oauthResponse = null,
        string $refreshToken = 'refresh-token'
    ): TokenSet {
        $defaultResponse = [
            'access_token' => 'oauth-access-token',
            'expires_in' => 3600,
            'token_type' => 'Bearer',
            'scope' => 'read write'
        ];

        return TokenSet::fromOAuthResponse(
            $oauthResponse ?? $defaultResponse,
            $refreshToken
        );
    }
}