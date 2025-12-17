<?php

declare(strict_types=1);

namespace League\OAuth2\Client\Test\Provider;

use League\OAuth2\Client\Provider\Geocaching;
use League\OAuth2\Client\Provider\GeocachingConfig;
use League\OAuth2\Client\Test\Factory\GeocachingTestFactory;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class GeocachingCustomUrlTest extends TestCase
{
    public function testConstructorWithCustomUrls(): void
    {
        $customDomain = 'https://my-custom-geocaching.example.com';
        $customApiDomain = 'https://api.my-custom-geocaching.example.com';
        $customOAuthDomain = 'https://oauth.my-custom-geocaching.example.com';

        $provider = new Geocaching([
            'clientId'     => 'test-client',
            'clientSecret' => 'test-secret',
            'environment'  => 'dev',
            'redirectUri'  => 'http://localhost/callback',
            'domain'       => $customDomain,
            'apiDomain'    => $customApiDomain,
            'oAuthDomain'  => $customOAuthDomain,
        ]);

        $this->assertEquals($customDomain, $this->getProperty($provider, 'domain'));
        $this->assertEquals($customApiDomain, $this->getProperty($provider, 'apiDomain'));
        $this->assertEquals($customOAuthDomain, $this->getProperty($provider, 'oAuthDomain'));
    }

    public function testConstructorWithPartialCustomUrls(): void
    {
        $customApiDomain = 'https://my-api.example.com';

        $provider = new Geocaching([
            'clientId'     => 'test-client',
            'clientSecret' => 'test-secret',
            'environment'  => 'dev',
            'redirectUri'  => 'http://localhost/callback',
            'apiDomain'    => $customApiDomain,
            // domain and oAuthDomain should use dev environment defaults
        ]);

        // Custom override
        $this->assertEquals($customApiDomain, $this->getProperty($provider, 'apiDomain'));

        // Environment defaults (dev)
        $this->assertEquals('http://localhost:8000', $this->getProperty($provider, 'domain'));
        $this->assertEquals('http://localhost:8000', $this->getProperty($provider, 'oAuthDomain'));
    }

    public function testConstructorFallsBackToEnvironmentDefaults(): void
    {
        $provider = new Geocaching([
            'clientId'     => 'test-client',
            'clientSecret' => 'test-secret',
            'environment'  => 'production',
            'redirectUri'  => 'http://localhost/callback',
            // No custom URLs provided
        ]);

        // Should use production environment defaults
        $this->assertEquals('https://www.geocaching.com', $this->getProperty($provider, 'domain'));
        $this->assertEquals('https://api.groundspeak.com', $this->getProperty($provider, 'apiDomain'));
        $this->assertEquals('https://oauth.geocaching.com', $this->getProperty($provider, 'oAuthDomain'));
    }

    public function testFactoryCreateWithCustomUrls(): void
    {
        $provider = GeocachingTestFactory::createWithCustomUrls(
            'https://dev.geocaching.local',
            'https://api.dev.geocaching.local',
            'https://oauth.dev.geocaching.local'
        );

        $this->assertEquals('https://dev.geocaching.local', $this->getProperty($provider, 'domain'));
        $this->assertEquals('https://api.dev.geocaching.local', $this->getProperty($provider, 'apiDomain'));
        $this->assertEquals('https://oauth.dev.geocaching.local', $this->getProperty($provider, 'oAuthDomain'));
    }

    public function testFactoryCreateForDocker(): void
    {
        $provider = GeocachingTestFactory::createForDocker(9000);

        $this->assertEquals('http://localhost:9000', $this->getProperty($provider, 'domain'));
        $this->assertEquals('http://localhost:9000/api', $this->getProperty($provider, 'apiDomain'));
        $this->assertEquals('http://localhost:9000/oauth', $this->getProperty($provider, 'oAuthDomain'));
    }

    public function testFactoryCreateForLocalDev(): void
    {
        $provider = GeocachingTestFactory::createForLocalDev('http://192.168.1.100:8080');

        $this->assertEquals('http://192.168.1.100:8080', $this->getProperty($provider, 'domain'));
        $this->assertEquals('http://192.168.1.100:8080/api/v1', $this->getProperty($provider, 'apiDomain'));
        $this->assertEquals('http://192.168.1.100:8080/oauth', $this->getProperty($provider, 'oAuthDomain'));
    }

    public function testCustomUrlsAffectGeneratedUrls(): void
    {
        $provider = GeocachingTestFactory::createWithCustomUrls(
            'https://my-geocaching.local',
            'https://api.my-geocaching.local',
            'https://oauth.my-geocaching.local'
        );

        $this->assertEquals(
            'https://my-geocaching.local/oauth/authorize.aspx',
            $provider->getBaseAuthorizationUrl()
        );

        $this->assertEquals(
            'https://oauth.my-geocaching.local/token',
            $provider->getBaseAccessTokenUrl([])
        );

        // ResourceOwnerDetailsUrl should use the apiDomain
        $mockToken = new \League\OAuth2\Client\Token\AccessToken(['access_token' => 'test']);
        $resourceOwnerUrl = $provider->getResourceOwnerDetailsUrl($mockToken);
        $this->assertStringStartsWith('https://api.my-geocaching.local/v1/users/me', $resourceOwnerUrl);
    }

    public function testGeocachingConfigcreate(): void
    {
        $customConfig = GeocachingConfig::create('dev', [
            'apiDomain' => 'https://custom-api.example.com',
        ]);

        $this->assertEquals('http://localhost:8000', $customConfig['domain']); // from dev environment
        $this->assertEquals('https://custom-api.example.com', $customConfig['apiDomain']); // custom override
        $this->assertEquals('http://localhost:8000', $customConfig['oAuthDomain']); // from dev environment
    }

    public function testGeocachingConfigcreateFiltersEmptyValues(): void
    {
        $customConfig = GeocachingConfig::create('production', [
            'domain' => 'https://custom-domain.com',
            'apiDomain' => '', // Empty string should be filtered out
            'oAuthDomain' => null, // Null should be filtered out
        ]);

        $this->assertEquals('https://custom-domain.com', $customConfig['domain']); // custom override
        $this->assertEquals('https://api.groundspeak.com', $customConfig['apiDomain']); // production default (not overridden)
        $this->assertEquals('https://oauth.geocaching.com', $customConfig['oAuthDomain']); // production default (not overridden)
    }

    private function getProperty(object $object, string $name): mixed
    {
        $property = new ReflectionProperty($object, $name);
        return $property->getValue($object);
    }
}