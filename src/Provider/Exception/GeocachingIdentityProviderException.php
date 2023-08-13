<?php

namespace League\OAuth2\Client\Provider\Exception;

use Psr\Http\Message\ResponseInterface;

final class GeocachingIdentityProviderException extends IdentityProviderException
{
    /**
     * Creates client exception from response.
     */
    public static function clientException(ResponseInterface $response, array $data): IdentityProviderException
    {
        return static::fromResponse(
            $response,
            $data['message'] ?? $response->getReasonPhrase()
        );
    }

    /**
     * Creates oauth exception from response.
     */
    public static function oauthException(ResponseInterface $response, array $data): IdentityProviderException
    {
        return static::fromResponse(
            $response,
            $data['error'] ?? $response->getReasonPhrase()
        );
    }

    /**
     * Creates identity exception from response.
     */
    protected static function fromResponse(ResponseInterface $response, ?string $message = null): IdentityProviderException
    {
        return new static($message, $response->getStatusCode(), (string) $response->getBody());
    }
}
