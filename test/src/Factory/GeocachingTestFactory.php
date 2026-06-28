<?php

declare(strict_types=1);

namespace League\OAuth2\Client\Test\Factory;

use League\OAuth2\Client\Provider\Geocaching;

/**
 * Factory class to create Geocaching provider instances for testing.
 */
class GeocachingTestFactory
{
    /**
     * Create a development environment provider.
     */
    public static function createDev(array $overrides = []): Geocaching
    {
        $defaults = [
            'clientId'     => 'dev-client-id',
            'clientSecret' => 'dev-client-secret',
            'environment'  => 'dev',
            'redirectUri'  => 'http://localhost:3000/callback',
            'scope'        => '*',
        ];

        return new Geocaching(array_merge($defaults, $overrides));
    }

    /**
     * Create a staging environment provider.
     */
    public static function createStaging(array $overrides = []): Geocaching
    {
        $defaults = [
            'clientId'     => 'staging-client-id',
            'clientSecret' => 'staging-client-secret',
            'environment'  => 'staging',
            'redirectUri'  => 'https://staging.example.com/callback',
            'scope'        => '*',
        ];

        return new Geocaching(array_merge($defaults, $overrides));
    }

    /**
     * Create a production environment provider.
     */
    public static function createProduction(array $overrides = []): Geocaching
    {
        $defaults = [
            'clientId'     => 'prod-client-id',
            'clientSecret' => 'prod-client-secret',
            'environment'  => 'production',
            'redirectUri'  => 'https://example.com/callback',
            'scope'        => '*',
        ];

        return new Geocaching(array_merge($defaults, $overrides));
    }

    /**
     * Create a provider with custom resource owner fields.
     */
    public static function createWithCustomFields(array $resourceOwnerFields): Geocaching
    {
        $provider = self::createDev();
        $provider->setResourceOwnerFields($resourceOwnerFields);

        return $provider;
    }

    /**
     * Create a provider with minimal configuration for testing.
     */
    public static function createMinimal(): Geocaching
    {
        return new Geocaching([
            'clientId'     => 'test-id',
            'clientSecret' => 'test-secret',
            'environment'  => 'dev',
            'redirectUri'  => 'http://test.local/callback',
        ]);
    }

    /**
     * Create a provider with completely custom URLs.
     */
    public static function createWithCustomUrls(
        string $domain,
        string $apiDomain,
        string $oAuthDomain,
        array $overrides = []
    ): Geocaching {
        $defaults = [
            'clientId'     => 'custom-client-id',
            'clientSecret' => 'custom-client-secret',
            'environment'  => 'dev', // Base environment (required)
            'redirectUri'  => 'http://localhost:3000/callback',
            'domain'       => $domain,
            'apiDomain'    => $apiDomain,
            'oAuthDomain'  => $oAuthDomain,
        ];

        return new Geocaching(array_merge($defaults, $overrides));
    }

    /**
     * Create a provider with Docker/local development setup.
     */
    public static function createForDocker(int $port = 8080): Geocaching
    {
        return self::createWithCustomUrls(
            "http://localhost:{$port}",
            "http://localhost:{$port}/api",
            "http://localhost:{$port}/oauth"
        );
    }

    /**
     * Create a provider for local development with custom API endpoints.
     */
    public static function createForLocalDev(string $baseUrl = 'http://localhost:3000'): Geocaching
    {
        return self::createWithCustomUrls(
            $baseUrl,
            "{$baseUrl}/api/v1",
            "{$baseUrl}/oauth"
        );
    }
}