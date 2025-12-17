<?php

declare(strict_types=1);

namespace League\OAuth2\Client\Test\Token;

use League\OAuth2\Client\Token\TokenSet;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TokenSetTest extends TestCase
{
    public function testFromOAuthResponseBuildsTokenSet(): void
    {
        $tokens = TokenSet::fromOAuthResponse([
            'access_token'  => 'abc',
            'refresh_token' => 'def',
            'expires_in'    => 120,
            'token_type'    => 'Bearer',
            'scope'         => 'foo bar',
        ]);

        self::assertSame('abc', $tokens->accessToken);
        self::assertSame('def', $tokens->refreshToken);
        self::assertSame('Bearer abc', $tokens->getAuthorizationHeader());
        self::assertGreaterThan(0, $tokens->getSecondsUntilExpiry());
        self::assertSame(['foo', 'bar'], $tokens->scopes);
    }

    public function testFromOAuthResponseRequiresRefreshToken(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TokenSet::fromOAuthResponse(['access_token' => 'abc'], null);
    }

    public function testCreateThrowsOnEmptyValues(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TokenSet::create('', 'refresh');
    }

    public function testCreateThrowsOnEmptyRefreshToken(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TokenSet::create('access', '');
    }

    public function testIsExpiredAndBuffer(): void
    {
        $tokens = TokenSet::create('access', 'refresh', 1);
        sleep(2);

        self::assertTrue($tokens->isExpired(), 'Token should be expired after sleep');
    }

    public function testRoundTripArraySerialization(): void
    {
        $original = TokenSet::create('access', 'refresh', 300);
        $array    = $original->toArray();
        $restored = TokenSet::fromArray($array);

        self::assertSame($original->accessToken, $restored->accessToken);
        self::assertSame($original->refreshToken, $restored->refreshToken);
        self::assertSame($original->tokenType, $restored->tokenType);
        self::assertSame($original->expiresAt->format('Y-m-d H:i'), $restored->expiresAt->format('Y-m-d H:i'));
    }
}
