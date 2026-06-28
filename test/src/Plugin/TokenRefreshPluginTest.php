<?php

declare(strict_types=1);

namespace League\OAuth2\Client\Test\Plugin;

use Http\Promise\FulfilledPromise;
use League\OAuth2\Client\Plugin\TokenRefreshPlugin;
use League\OAuth2\Client\Provider\Geocaching;
use League\OAuth2\Client\Provider\Exception\GeocachingIdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\TokenSet;
use League\OAuth2\Client\Token\TokenStorageInterface;
use League\OAuth2\Client\Exception\TokenRefreshException;
use League\OAuth2\Client\Exception\RefreshTokenExpiredException;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Http\Promise\RejectedPromise;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class TokenRefreshPluginTest extends TestCase
{
    public function testForceRefreshOn401EvenWhenTokenNotExpired(): void
    {
        $reference = 'PR1';

        $initialTokens = TokenSet::create('old-access', 'refresh-token', 3600);

        /** @var TokenStorageInterface&MockObject $storage */
        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->expects($this->once())->method('lockUser')->with($reference)->willReturn(true);
        $storage->expects($this->once())->method('unlockUser')->with($reference);
        $storage->expects($this->once())->method('getTokens')->with($reference)->willReturn($initialTokens);
        $storage->expects($this->once())->method('saveTokens')->with(
            $reference,
            $this->callback(fn (TokenSet $tokens) => $tokens->accessToken === 'new-access')
        );

        /** @var Geocaching&MockObject $provider */
        $provider = $this->createMock(Geocaching::class);
        $provider->expects($this->once())
            ->method('getAccessToken')
            ->with('refresh_token', ['refresh_token' => 'refresh-token'])
            ->willReturn(new AccessToken(['access_token' => 'new-access', 'refresh_token' => 'new-refresh', 'expires' => time() + 3600]));

        $plugin = new TokenRefreshPlugin($reference, $storage, $provider);

        $capturedRequest = null;
        $next = fn ($request) => new FulfilledPromise(new Response(401));
        $first = function ($request) use (&$capturedRequest) {
            $capturedRequest = $request;
            return new FulfilledPromise(new Response(200));
        };

        $response = $plugin->handleRequest(new Request('GET', 'https://example.com'), $next, $first)->wait();

        self::assertSame(200, $response->getStatusCode(), 'Should return retried response');
        self::assertSame('Bearer new-access', $capturedRequest->getHeaderLine('Authorization'));
    }

    public function testReturnsExistingTokenWhenNotExpiredAndNoForceRefresh(): void
    {
        $reference = 'PR1';
        $initialTokens = TokenSet::create('old-access', 'refresh-token', 3600);

        /** @var TokenStorageInterface&MockObject $storage */
        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->expects($this->once())->method('lockUser')->with($reference)->willReturn(true);
        $storage->expects($this->once())->method('unlockUser')->with($reference);
        $storage->expects($this->once())->method('getTokens')->with($reference)->willReturn($initialTokens);
        $storage->expects($this->never())->method('saveTokens');

        /** @var Geocaching&MockObject $provider */
        $provider = $this->createMock(Geocaching::class);
        $provider->expects($this->never())->method('getAccessToken');

        $plugin = new TokenRefreshPlugin($reference, $storage, $provider);

        $method = new \ReflectionMethod(TokenRefreshPlugin::class, 'refreshAccessToken');

        /** @var TokenSet $result */
        $result = $method->invoke($plugin, false);

        self::assertSame('old-access', $result->accessToken);
    }

    public function testHandleRequestPassThroughOnSuccessResponse(): void
    {
        $reference = 'PR1';
        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->method('lockUser')->willReturn(true);
        $storage->method('unlockUser');
        $storage->method('getTokens')->willReturn(TokenSet::create('token', 'refresh', 3600));

        $provider = $this->createMock(Geocaching::class);

        $plugin = new TokenRefreshPlugin($reference, $storage, $provider);

        $next = fn ($request) => new FulfilledPromise(new Response(200));

        $response = $plugin->handleRequest(new Request('GET', 'https://example.com'), $next, $next)->wait();

        self::assertSame(200, $response->getStatusCode(), 'Non-401 responses should pass through untouched');
    }

    public function testRefreshAccessTokenThrowsWhenLockUnavailable(): void
    {
        $reference = 'PR1';
        $storage   = $this->createMock(TokenStorageInterface::class);
        $storage->expects($this->once())->method('lockUser')->with($reference)->willReturn(false);

        $provider = $this->createMock(Geocaching::class);

        $plugin = new TokenRefreshPlugin($reference, $storage, $provider, null, 1);

        $method = new \ReflectionMethod(TokenRefreshPlugin::class, 'refreshAccessToken');

        $this->expectException(\League\OAuth2\Client\Exception\TokenStorageException::class);
        $method->invoke($plugin, false);
    }

    public function testPerformTokenRefreshThrowsWhenNoTokensInStorage(): void
    {
        $reference = 'PR1';
        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->expects($this->once())->method('getTokens')->with($reference)->willReturn(null);

        $provider = $this->createMock(Geocaching::class);

        $plugin = new TokenRefreshPlugin($reference, $storage, $provider);

        $method = new \ReflectionMethod(TokenRefreshPlugin::class, 'performTokenRefresh');

        $this->expectException(\League\OAuth2\Client\Exception\TokenStorageException::class);
        $method->invoke($plugin, false);
    }

    public function testRefreshTokenAndRetryPropagatesTokenRefreshException(): void
    {
        $reference = 'PR1';
        $storage   = $this->createMock(TokenStorageInterface::class);
        $storage->method('lockUser')->willReturn(true);
        $storage->method('unlockUser');
        $storage->method('getTokens')->willReturn(TokenSet::create('expired', 'refresh-token', -10));

        $provider = $this->createMock(Geocaching::class);
        $provider->method('getAccessToken')->willThrowException(new \RuntimeException('boom'));

        $plugin = new TokenRefreshPlugin($reference, $storage, $provider);

        $this->expectException(TokenRefreshException::class);
        $method = new \ReflectionMethod(TokenRefreshPlugin::class, 'refreshTokenAndRetry');
        $method->invoke($plugin, new Request('GET', 'https://example.com'), fn ($req) => new FulfilledPromise(new Response(200)));
    }

    public function testRefreshTokenAndRetryThrowsWhenRefreshTokenExpired(): void
    {
        $reference = 'PR1';
        $storage   = $this->createMock(TokenStorageInterface::class);
        $storage->method('lockUser')->willReturn(true);
        $storage->method('unlockUser');
        $storage->method('getTokens')->willReturn(TokenSet::create('expired', 'refresh-token', -10));

        $response = new Response(400, [], json_encode(['error' => 'invalid_grant', 'error_description' => 'expired']));
        $provider = $this->createMock(Geocaching::class);
        $provider->method('getAccessToken')->willThrowException(
            GeocachingIdentityProviderException::oauthException($response, ['error' => 'invalid_grant'])
        );

        $plugin = new TokenRefreshPlugin($reference, $storage, $provider);

        $this->expectException(RefreshTokenExpiredException::class);
        $method = new \ReflectionMethod(TokenRefreshPlugin::class, 'refreshTokenAndRetry');
        $method->invoke($plugin, new Request('GET', 'https://example.com'), fn ($req) => new FulfilledPromise(new Response(200)));
    }

    public function testRefreshAccessTokenReturnsTokenFromOtherProcess(): void
    {
        $reference = 'PR1';
        $fresh     = TokenSet::create('fresh', 'refresh-token', 3600);

        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->expects($this->once())->method('lockUser')->willReturn(false);
        $storage->expects($this->once())->method('getTokens')->willReturn($fresh);

        $provider = $this->createMock(Geocaching::class);

        $plugin = new TokenRefreshPlugin($reference, $storage, $provider);

        $method = new \ReflectionMethod(TokenRefreshPlugin::class, 'refreshAccessToken');

        $result = $method->invoke($plugin, false);

        self::assertSame('fresh', $result->accessToken);
    }

    public function testCallOAuthRefreshEndpointMapsProviderErrorToTokenRefreshException(): void
    {
        $reference = 'PR1';
        $storage   = $this->createMock(TokenStorageInterface::class);
        $storage->method('lockUser')->willReturn(true);
        $storage->method('unlockUser');
        $storage->method('getTokens')->willReturn(TokenSet::create('expired', 'refresh-token', -10));

        $response = new Response(400, [], json_encode(['error' => 'server_error', 'message' => 'bad']));
        $provider = $this->createMock(Geocaching::class);
        $provider->method('getAccessToken')->willThrowException(
            GeocachingIdentityProviderException::oauthException($response, ['error' => 'server_error'])
        );

        $plugin = new TokenRefreshPlugin($reference, $storage, $provider);

        $method = new \ReflectionMethod(TokenRefreshPlugin::class, 'callOAuthRefreshEndpoint');

        $this->expectException(TokenRefreshException::class);
        $method->invoke($plugin, 'refresh-token');
    }

    public function testCallOAuthRefreshEndpointWrapsUnexpectedException(): void
    {
        $reference = 'PR1';
        $storage   = $this->createMock(TokenStorageInterface::class);
        $provider  = $this->createMock(Geocaching::class);
        $provider->method('getAccessToken')->willThrowException(new \RuntimeException('network down'));

        $plugin = new TokenRefreshPlugin($reference, $storage, $provider);
        $method = new \ReflectionMethod(TokenRefreshPlugin::class, 'callOAuthRefreshEndpoint');

        $this->expectException(TokenRefreshException::class);
        $method->invoke($plugin, 'refresh-token');
    }

    public function testHandleRequestPropagatesPromiseRejection(): void
    {
        $reference = 'PR1';
        $storage   = $this->createMock(TokenStorageInterface::class);
        $provider  = $this->createMock(Geocaching::class);

        $plugin = new TokenRefreshPlugin($reference, $storage, $provider);

        $next = fn ($request) => new RejectedPromise(new \RuntimeException('fail'));

        $this->expectException(\RuntimeException::class);
        $plugin->handleRequest(new Request('GET', 'https://example.com'), $next, $next)->wait();
    }

    public function testRefreshTokenAndRetryPropagatesRetryFailure(): void
    {
        $reference = 'PR1';
        $storage   = $this->createMock(TokenStorageInterface::class);
        $storage->method('lockUser')->willReturn(true);
        $storage->method('unlockUser');
        $storage->method('getTokens')->willReturn(TokenSet::create('expired', 'refresh-token', -10));

        $provider = $this->createMock(Geocaching::class);
        $provider->method('getAccessToken')->willReturn(new AccessToken(['access_token' => 'new', 'refresh_token' => 'refresh', 'expires' => time() + 3600]));

        $plugin = new TokenRefreshPlugin($reference, $storage, $provider);

        $method = new \ReflectionMethod(TokenRefreshPlugin::class, 'refreshTokenAndRetry');

        $this->expectException(\RuntimeException::class);
        $method->invoke($plugin, new Request('GET', 'https://example.com'), fn ($req) => new RejectedPromise(new \RuntimeException('retry fail')))->wait();
    }
}
