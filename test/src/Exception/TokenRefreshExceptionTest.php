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

    public function testRefreshTokenExpiredExtendsTokenRefreshException(): void
    {
        $expired = new RefreshTokenExpiredException('expired');

        self::assertInstanceOf(TokenRefreshException::class, $expired);
    }
}
