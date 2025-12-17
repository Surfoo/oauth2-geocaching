<?php

namespace League\OAuth2\Client\Test\Provider;

use InvalidArgumentException;
use League\OAuth2\Client\Provider\Exception\GeocachingIdentityProviderException;
use League\OAuth2\Client\Test\Geocaching as MockProvider;
use League\OAuth2\Client\Provider\Geocaching as GeocachingProvider;
use League\OAuth2\Client\Provider\GeocachingResourceOwner;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Psr\Http\Message\ResponseInterface;
use ReflectionClass;
use ReflectionProperty;

#[AllowMockObjectsWithoutExpectations]
class GeocachingTest extends TestCase
{

    protected $provider;

    public function testRequiredOptions()
    {
        // Additionally, these options are required by the GenericProvider
        $required = [
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'none',
            'scope' => '*',
            'environment' => 'test',
            'pkceMethod' => 'S256',
        ];

        foreach ($required as $key => $value) {
            // Test each of the required options by removing a single value
            // and attempting to create a new provider.
            $options = $required;
            unset($options[$key]);

            try {
                new GeocachingProvider($options);
            } catch (\Exception $e) {
                $this->assertInstanceOf(InvalidArgumentException::class, $e);
            }
        }

        new GeocachingProvider($required + []);
    }

    public function testConfigurableOptions()
    {
        $options = [
            'clientId'       => 'mock_client_id',
            'clientSecret'   => 'mock_secret',
            'environment'    => 'dev',
            'pkceMethod'     => 'S256',
            'redirectUri'    => 'none',
        ];

        $provider = new GeocachingProvider($options + [
            'scope'          => '*',
        ]);

        foreach ($options as $key => $expected) {
            $property = new ReflectionProperty(GeocachingProvider::class, $key);

            $this->assertEquals($expected, $property->getValue($provider));
        }

        $this->assertEquals('http://localhost:8000/oauth/authorize.aspx', $provider->getBaseAuthorizationUrl());
        $this->assertEquals('http://localhost:8000/token', $provider->getBaseAccessTokenUrl([]));
        $this->assertEquals('http://localhost:8000/v1/users/me?fields=referenceCode%2CfindCount%2ChideCount%2CfavoritePoints%2Cusername%2CmembershipLevelId%2CjoinedDateUtc', $provider->getResourceOwnerDetailsUrl(new AccessToken(['access_token' => '1234'])));
        $this->assertEquals(['*'], $provider->getDefaultScopes());

        $reflection = new ReflectionClass(get_class($provider));

        $getPkceMethod = $reflection->getMethod('getPkceMethod');
 
        $this->assertEquals($options['pkceMethod'], $getPkceMethod->invoke($provider));
    }

    public function testGetConfigurableOptions()
    {
        $options = [
            'clientId'       => 'mock_client_id',
            'clientSecret'   => 'mock_secret',
            'environment'    => 'dev',
            'pkceMethod'     => 'S256',
            'redirectUri'    => 'none',
        ];

        $provider = new GeocachingProvider($options + [
            'scope'          => '*',
        ]);

        $reflection = new ReflectionClass(get_class($provider));

        $getConfigurableOptions = $reflection->getMethod('getConfigurableOptions');

        $this->assertIsArray($getConfigurableOptions->invoke($provider));
    }

    public function testStagingEnvironmentDomains()
    {
        $provider = new GeocachingProvider([
            'clientId'       => 'mock_client_id',
            'clientSecret'   => 'mock_secret',
            'environment'    => 'staging',
            'pkceMethod'     => 'S256',
            'redirectUri'    => 'none',
            'scope'          => '*',
        ]);

        $this->assertEquals('https://staging.geocaching.com', $this->getProperty($provider, 'domain'));
        $this->assertEquals('https://staging.api.groundspeak.com', $this->getProperty($provider, 'apiDomain'));
        $this->assertEquals('https://oauth-staging.geocaching.com', $this->getProperty($provider, 'oAuthDomain'));
    }

    public function testResourceOwnerDetails()
    {
        $token = new AccessToken(['access_token' => 'mock_token']);

        $provider = new MockProvider([
            'clientId'       => 'mock_client_id',
            'clientSecret'   => 'mock_secret',
            'environment'    => 'dev',
            'pkceMethod'     => 'S256',
            'redirectUri'    => 'none',
        ]);

        $user = $provider->getResourceOwner($token);

        $this->assertInstanceOf(GeocachingResourceOwner::class, $user);
        $this->assertEquals('PR1QQQP', $user->getId());
        $this->assertEquals('PR1QQQP', $user->getReferenceCode());
        $this->assertEquals('testmock', $user->getUsername());
        $this->assertEquals('3', $user->getMembershipLevelId());
        $this->assertEquals('2000-12-31T10:10:10.123', $user->getJoinedDate());
        $this->assertEquals(42, $user->getFindCount());
        $this->assertEquals(24, $user->getHideCount());
        $this->assertEquals(100, $user->getFavoritePoints());
        $this->assertEquals('https://img.geocaching.com/large/avatar.jpg', $user->getAvatarUrl());
        $this->assertEquals('https://www.geocaching.com/account/app/ui-images/components/profile/p_bgimage-large.png', $user->getBannerUrl());
        $this->assertEquals('https://coord.info/PR1QQQP', $user->getProfileUrl());
        $this->assertEquals('lorem lipsum', $user->getProfileText());
        $this->assertIsArray($user->getHomeCoordinates());
        $this->assertTrue($user->getOptedInFriendSharing());
        $this->assertIsArray($user->getGeocacheLimits());

        $data = $user->toArray();

        $this->assertEquals('PR1QQQP', $data['referenceCode']);
        $this->assertEquals('testmock', $data['username']);
        $this->assertEquals('2000-12-31T10:10:10.123', $data['joinedDateUtc']);
        $this->assertEquals(100, $data['favoritePoints']);
        $this->assertEquals(3, $data['membershipLevelId']);
        $this->assertEquals(42, $data['findCount']);
        $this->assertEquals(24, $data['hideCount']);
        $this->assertEquals('https://img.geocaching.com/large/avatar.jpg', $data['avatarUrl']);
        $this->assertEquals('https://www.geocaching.com/account/app/ui-images/components/profile/p_bgimage-large.png', $data['bannerUrl']);
        $this->assertEquals('https://coord.info/PR1QQQP', $data['url']);
    }

    private function getProperty(object $object, string $name): mixed
    {
        $property = new ReflectionProperty($object, $name);

        return $property->getValue($object);
    }

    public function testCheckResponse()
    {
        $mockedResponse = $this->createMock(ResponseInterface::class);
        $mockedResponse->expects($this->any())->method('getStatusCode')->willReturn(200);

        $options = [
            'clientId'       => 'mock_client_id',
            'clientSecret'   => 'mock_secret',
            'environment'    => 'dev',
            'pkceMethod'     => 'S256',
            'redirectUri'    => 'none',
        ];

        $provider = new GeocachingProvider($options);

        $reflection = new ReflectionClass(get_class($provider));
        $checkResponse = $reflection->getMethod('checkResponse');

        $this->assertNull($checkResponse->invokeArgs($provider, [$mockedResponse, []]));
    }

    public function testCheckResponseWithError()
    {
        $mockedResponse = $this->createMock(ResponseInterface::class);
        $mockedResponse->expects($this->once())->method('getStatusCode')->willReturn(400);
        $mockedResponse->expects($this->any())->method('getBody')->willReturn($this->createMock(\Psr\Http\Message\StreamInterface::class));

        $options = [
            'clientId'       => 'mock_client_id',
            'clientSecret'   => 'mock_secret',
            'environment'    => 'dev',
            'pkceMethod'     => 'S256',
            'redirectUri'    => 'none',
        ];

        $provider = new GeocachingProvider($options);

        $reflection = new ReflectionClass(get_class($provider));
        $checkResponse = $reflection->getMethod('checkResponse');

        $this->expectException(GeocachingIdentityProviderException::class);

        $checkResponse->invokeArgs($provider, [$mockedResponse, ['error' => 'Bad request']]);
    }

    public function testCheckResponseWithClientError()
    {
        $mockedResponse = $this->createMock(ResponseInterface::class);
        $mockedResponse->expects($this->exactly(2))->method('getStatusCode')->willReturn(401);
        $mockedResponse->expects($this->once())->method('getReasonPhrase')->willReturn('Unauthorized');
        $mockedResponse->expects($this->any())->method('getBody')->willReturn($this->createMock(\Psr\Http\Message\StreamInterface::class));

        $options = [
            'clientId'       => 'mock_client_id',
            'clientSecret'   => 'mock_secret',
            'environment'    => 'dev',
            'pkceMethod'     => 'S256',
            'redirectUri'    => 'none',
        ];

        $provider = new GeocachingProvider($options);

        $reflection = new ReflectionClass(get_class($provider));
        $checkResponse = $reflection->getMethod('checkResponse');

        $this->expectException(GeocachingIdentityProviderException::class);

        $checkResponse->invokeArgs($provider, [$mockedResponse, []]);
    }

    public function testGetResourceOwnerFields()
    {
        $provider = new MockProvider(['environment' => 'dev']);
        $response = $provider->getResourceOwnerFields();

        $this->assertEquals([
            'referenceCode',
            'findCount',
            'hideCount',
            'favoritePoints',
            'username',
            'membershipLevelId',
            'joinedDateUtc',
        ], $response);
    }

    public function testSetResourceOwnerFields()
    {
        $provider = new MockProvider(['environment' => 'dev']);
        $response = $provider->setResourceOwnerFields(['referenceCode'])
                             ->getResourceOwnerFields();

        $this->assertEquals(['referenceCode'], $response);
    }
}
