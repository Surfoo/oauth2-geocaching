<?php

declare(strict_types=1);

namespace League\OAuth2\Client\Token;

/**
 * Interface for token storage implementations.
 *
 * Allows users to implement their own storage mechanism (database, Redis, file, etc.)
 * with concurrent access protection.
 */
interface TokenStorageInterface
{
    /**
     * Retrieve tokens for a specific user.
     *
     * @param  string        $referenceCode The user reference code
     * @return TokenSet|null The token set or null if not found
     */
    public function getTokens(string $referenceCode): ?TokenSet;

    /**
     * Save tokens for a specific user.
     *
     * @param string   $referenceCode The user reference code
     * @param TokenSet $tokens        The tokens to save
     */
    public function saveTokens(string $referenceCode, TokenSet $tokens): void;

    /**
     * Acquire exclusive lock for a user to prevent concurrent token refreshes.
     *
     * @param  string $referenceCode  The user reference code
     * @param  int    $timeoutSeconds Lock timeout in seconds (default: 30)
     * @return bool   True if lock acquired, false otherwise
     */
    public function lockUser(string $referenceCode, int $timeoutSeconds = 30): bool;

    /**
     * Release the lock for a user.
     *
     * @param string $referenceCode The user reference code
     */
    public function unlockUser(string $referenceCode): void;

    /**
     * Check if a user is currently locked by another process.
     *
     * @param  string $referenceCode The user reference code
     * @return bool   True if locked, false otherwise
     */
    public function isUserLocked(string $referenceCode): bool;
}
