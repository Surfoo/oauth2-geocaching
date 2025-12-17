<?php

declare(strict_types=1);

namespace League\OAuth2\Client\Test\Exception;

use League\OAuth2\Client\Exception\RefreshTokenExpiredException;
use League\OAuth2\Client\Exception\TokenRefreshException;
use PHPUnit\Framework\TestCase;

class TokenRefreshExceptionTest extends TestCase
{
    public function testStoresResponseData(): void
    {
        $exception = new TokenRefreshException('fail', 0, null, ['error' => 'invalid_token']);

        self::assertSame(['error' => 'invalid_token'], $exception->getResponseData());
    }

    public function testFactoryWrapsPrevious(): void
    {
        $previous = new \RuntimeException('oauth down', 500);

        $exception = TokenRefreshException::fromOAuthResponse($previous, ['foo' => 'bar']);

        self::assertSame('OAuth provider error: oauth down', $exception->getMessage());
        self::assertSame(500, $exception->getCode());
        self::assertSame($previous, $exception->getPrevious());
        self::assertSame(['foo' => 'bar'], $exception->getResponseData());
    }

    public function testRefreshTokenExpiredExtendsTokenRefreshException(): void
    {
        $expired = new RefreshTokenExpiredException('expired');

        self::assertInstanceOf(TokenRefreshException::class, $expired);
    }
}
