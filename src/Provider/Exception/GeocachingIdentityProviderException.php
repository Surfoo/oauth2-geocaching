<?php

namespace League\OAuth2\Client\Provider\Exception;

use Psr\Http\Message\ResponseInterface;

final class GeocachingIdentityProviderException extends IdentityProviderException
{
    /**
     * Creates client exception from response.
     */
    public static function clientException(ResponseInterface $response, $data): IdentityProviderException
    {
        $message = is_string($data) ? $data : ($data['message'] ?? $response->getReasonPhrase());

        return static::fromResponse($response, $message);
    }

    /**
     * Creates oauth exception from response.
     */
    public static function oauthException(ResponseInterface $response, $data): IdentityProviderException
    {
        $message = is_string($data) ? $data : ($data['error'] ?? $response->getReasonPhrase());

        return static::fromResponse($response, $message);
    }

    /**
     * Creates identity exception from response.
     */
    protected static function fromResponse(ResponseInterface $response, ?string $message = null): IdentityProviderException
    {
        return new static($message, $response->getStatusCode(), (string) $response->getBody());
    }
}
