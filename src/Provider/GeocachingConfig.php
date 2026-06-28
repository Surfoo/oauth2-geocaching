<?php

declare(strict_types=1);

namespace League\OAuth2\Client\Provider;

/**
 * Configuration class containing all environment-specific settings for Geocaching OAuth provider.
 */
final class GeocachingConfig
{
    public const VALID_ENVIRONMENTS = [
        'dev',
        'development',
        'docker',
        'staging',
        'qa',
        'production',
        'prod',
        'test'
    ];

    public const ENVIRONMENTS = [
        'dev' => [
            'domain'      => 'http://localhost:8000',
            'apiDomain'   => 'http://localhost:8000',
            'oAuthDomain' => 'http://localhost:8000',
        ],
        'development' => [
            'domain'      => 'http://localhost:8000',
            'apiDomain'   => 'http://localhost:8000',
            'oAuthDomain' => 'http://localhost:8000',
        ],
        'docker' => [
            'domain'      => 'http://localhost:8000',
            'apiDomain'   => 'http://localhost:8000',
            'oAuthDomain' => 'http://localhost:8000',
        ],
        'staging' => [
            'domain'      => 'https://staging.geocaching.com',
            'apiDomain'   => 'https://staging.api.groundspeak.com',
            'oAuthDomain' => 'https://oauth-staging.geocaching.com',
        ],
        'qa' => [
            'domain'      => 'https://staging.geocaching.com',
            'apiDomain'   => 'https://staging.api.groundspeak.com',
            'oAuthDomain' => 'https://oauth-staging.geocaching.com',
        ],
        'production' => [
            'domain'      => 'https://www.geocaching.com',
            'apiDomain'   => 'https://api.groundspeak.com',
            'oAuthDomain' => 'https://oauth.geocaching.com',
        ],
        'prod' => [
            'domain'      => 'https://www.geocaching.com',
            'apiDomain'   => 'https://api.groundspeak.com',
            'oAuthDomain' => 'https://oauth.geocaching.com',
        ],
        'test' => [
            'domain'      => 'http://localhost:8000',
            'apiDomain'   => 'http://localhost:8000',
            'oAuthDomain' => 'http://localhost:8000',
        ],
    ];

    public const DEFAULT_RESOURCE_OWNER_FIELDS = [
        'referenceCode',
        'findCount',
        'hideCount',
        'favoritePoints',
        'username',
        'membershipLevelId',
        'joinedDateUtc',
    ];

    public const DEFAULT_SCOPE = '*';
    public const DEFAULT_PKCE_METHOD = 'S256';
    public const RESPONSE_RESOURCE_OWNER_ID = 'referenceCode';

    /**
     * Get configuration for a specific environment.
     *
     * @param string $environment The environment name
     * @return array The configuration array for the environment
     * @throws \InvalidArgumentException If the environment is not valid
     */
    public static function getEnvironmentConfig(string $environment): array
    {
        if (!self::isValidEnvironment($environment)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid environment "%s". Valid options: %s',
                    $environment,
                    implode(', ', self::VALID_ENVIRONMENTS)
                )
            );
        }

        return self::ENVIRONMENTS[$environment];
    }

    /**
     * Check if an environment is valid.
     */
    public static function isValidEnvironment(string $environment): bool
    {
        return in_array($environment, self::VALID_ENVIRONMENTS, true);
    }

    /**
     * Get all valid environment names.
     */
    public static function getValidEnvironments(): array
    {
        return self::VALID_ENVIRONMENTS;
    }

    /**
     * Create a custom configuration with URL overrides.
     *
     * Creates a configuration array based on a predefined environment with optional URL overrides.
     * Empty strings and null values in overrides are automatically filtered out.
     *
     * @param string $baseEnvironment Base environment to start from ('dev', 'staging', 'production', etc.)
     * @param array  $overrides       Custom URLs to override (domain, apiDomain, oAuthDomain)
     * @return array The merged configuration with base environment + overrides
     *
     * @example
     * ```php
     * $config = GeocachingConfig::create('staging', [
     *     'apiDomain' => 'https://my-api.example.com',
     * ]);
     * // Result: staging URLs + custom API domain
     * ```
     */
    public static function create(string $baseEnvironment, array $overrides = []): array
    {
        $baseConfig = self::getEnvironmentConfig($baseEnvironment);

        return array_merge($baseConfig, array_filter($overrides, function ($value) {
            return $value !== null && $value !== '';
        }));
    }
}
