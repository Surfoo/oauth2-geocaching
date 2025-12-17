<?php

declare(strict_types=1);

namespace League\OAuth2\Client\Test\Provider;

use InvalidArgumentException;
use League\OAuth2\Client\Provider\GeocachingConfig;
use PHPUnit\Framework\TestCase;

class GeocachingConfigTest extends TestCase
{
    public function testGetEnvironmentConfigReturnsValidConfig(): void
    {
        $config = GeocachingConfig::getEnvironmentConfig('production');

        $this->assertArrayHasKey('domain', $config);
        $this->assertArrayHasKey('apiDomain', $config);
        $this->assertArrayHasKey('oAuthDomain', $config);
        $this->assertEquals('https://www.geocaching.com', $config['domain']);
        $this->assertEquals('https://api.groundspeak.com', $config['apiDomain']);
        $this->assertEquals('https://oauth.geocaching.com', $config['oAuthDomain']);
    }

    public function testGetEnvironmentConfigThrowsForInvalidEnvironment(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid environment "invalid"');

        GeocachingConfig::getEnvironmentConfig('invalid');
    }

    public function testIsValidEnvironmentReturnsTrueForValidEnvironments(): void
    {
        foreach (GeocachingConfig::VALID_ENVIRONMENTS as $environment) {
            $this->assertTrue(
                GeocachingConfig::isValidEnvironment($environment),
                "Environment '{$environment}' should be valid"
            );
        }
    }

    public function testIsValidEnvironmentReturnsFalseForInvalidEnvironment(): void
    {
        $this->assertFalse(GeocachingConfig::isValidEnvironment('invalid'));
    }

    public function testGetValidEnvironmentsReturnsAllValidEnvironments(): void
    {
        $validEnvironments = GeocachingConfig::getValidEnvironments();

        $this->assertIsArray($validEnvironments);
        $this->assertContains('dev', $validEnvironments);
        $this->assertContains('production', $validEnvironments);
        $this->assertContains('staging', $validEnvironments);
        $this->assertContains('test', $validEnvironments);
    }

    public function testDevEnvironmentConfiguration(): void
    {
        $config = GeocachingConfig::getEnvironmentConfig('dev');

        $this->assertEquals('http://localhost:8000', $config['domain']);
        $this->assertEquals('http://localhost:8000', $config['apiDomain']);
        $this->assertEquals('http://localhost:8000', $config['oAuthDomain']);
    }

    public function testStagingEnvironmentConfiguration(): void
    {
        $config = GeocachingConfig::getEnvironmentConfig('staging');

        $this->assertEquals('https://staging.geocaching.com', $config['domain']);
        $this->assertEquals('https://staging.api.groundspeak.com', $config['apiDomain']);
        $this->assertEquals('https://oauth-staging.geocaching.com', $config['oAuthDomain']);
    }
}