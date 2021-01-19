<?php namespace League\OAuth2\Client\Test\Provider;

use League\OAuth2\Client\Tool\QueryBuilderTrait;
use Mockery as m;

class GeocachingTest extends \PHPUnit\Framework\TestCase
{
    use QueryBuilderTrait;

    protected $provider;

    protected function setUp(): void
    {
        $this->provider = new \League\OAuth2\Client\Provider\Geocaching([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'none',
        ]);
    }

    public function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function domainProvider()
    {
        return [
            [
                'dev',
                \League\OAuth2\Client\Provider\Geocaching::DEV_DOMAIN,
                \League\OAuth2\Client\Provider\Geocaching::DEV_DOMAIN,
                \League\OAuth2\Client\Provider\Geocaching::DEV_DOMAIN,
            ],
            [
                'staging',
                \League\OAuth2\Client\Provider\Geocaching::STAGING_DOMAIN,
                \League\OAuth2\Client\Provider\Geocaching::STAGING_OAUTH_DOMAIN,
                \League\OAuth2\Client\Provider\Geocaching::STAGING_API_DOMAIN,
            ],
            [
                'prod',
                \League\OAuth2\Client\Provider\Geocaching::PRODUCTION_DOMAIN,
                \League\OAuth2\Client\Provider\Geocaching::PRODUCTION_OAUTH_DOMAIN,
                \League\OAuth2\Client\Provider\Geocaching::PRODUCTION_API_DOMAIN,
            ],
        ];
    }

    /**
     * @dataProvider domainProvider
     */
    public function testSetDomains($environment, $expectedDomain, $expectedOAuthDomain, $expectedApiDomain)
    {
        $this->provider = new \League\OAuth2\Client\Provider\Geocaching([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'none',
            'environment' => $environment
        ]);
        
        $this->assertEquals($expectedDomain, $this->provider->domain);
        $this->assertEquals($expectedOAuthDomain, $this->provider->oAuthDomain);
        $this->assertEquals($expectedApiDomain, $this->provider->apiDomain);
    }
    
    public function testAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertArrayHasKey('scope', $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertArrayHasKey('approval_prompt', $query);
        $this->assertNotNull($this->provider->getState());
    }


    public function testScopes()
    {
        $scopeSeparator = ',';
        $options = ['scope' => [uniqid(), uniqid()]];
        $query = ['scope' => implode($scopeSeparator, $options['scope'])];
        $url = $this->provider->getAuthorizationUrl($options);
        $encodedScope = $this->buildQueryString($query);
        $this->assertStringContainsString($encodedScope, $url);
    }

    public function testGetAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);

        $this->assertEquals('/oauth/authorize.aspx', $uri['path']);
    }

    public function testGetBaseAccessTokenUrl()
    {
        $params = [];

        $url = $this->provider->getBaseAccessTokenUrl($params);
        $uri = parse_url($url);

        $this->assertEquals('/token', $uri['path']);
    }

    public function testGetAccessToken()
    {
        $response = m::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getBody')->andReturn('{"access_token":"mock_access_token", "scope":"repo,gist", "token_type":"bearer"}');
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $response->shouldReceive('getStatusCode')->andReturn(200);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(1)->andReturn($response);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

        $this->assertEquals('mock_access_token', $token->getToken());
        $this->assertNull($token->getExpires());
        $this->assertNull($token->getRefreshToken());
        $this->assertNull($token->getResourceOwnerId());
    }

    public function testGeocachingEnterpriseDomainUrls()
    {
        $response = m::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getBody')->times(1)->andReturn('access_token=mock_access_token&expires=3600&refresh_token=mock_refresh_token&otherKey={1234}');
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'application/x-www-form-urlencoded']);
        $response->shouldReceive('getStatusCode')->andReturn(200);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(1)->andReturn($response);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

        $this->assertEquals($this->provider->domain . '/oauth/authorize.aspx', $this->provider->getBaseAuthorizationUrl());
        $this->assertEquals($this->provider->oAuthDomain . '/token', $this->provider->getBaseAccessTokenUrl([]));
        $this->assertEquals($this->provider->apiDomain . '/v1/users/me?fields=referenceCode%2CfindCount%2ChideCount%2CfavoritePoints%2Cusername%2CmembershipLevelId%2CjoinedDateUtc%2CavatarUrl%2CbannerUrl%2Curl%2ChomeCoordinates%2CgeocacheLimits%2CoptedInFriendSharing', $this->provider->getResourceOwnerDetailsUrl($token));
    }

    public function testUserData()
    {
        $userId = 'PR27A92';
        $username = 'MNofMind';

        $postResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $postResponse->shouldReceive('getBody')->andReturn('access_token=mock_access_token&expires=3600&refresh_token=mock_refresh_token&otherKey={1234}');
        $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'application/x-www-form-urlencoded']);
        $postResponse->shouldReceive('getStatusCode')->andReturn(200);

        $userResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $userResponse->shouldReceive('getBody')->andReturn('{
            "referenceCode": "PR27A92",
            "findCount": 326,
            "hideCount": 0,
            "favoritePoints": 18,
            "username": "MNofMind",
            "membershipLevelId": 3,
            "avatarUrl": "https://img-stage.geocaching.com/gcstage/{0}/0640f488-9abe-4c2a-a786-bb75cec84357.gif",
            "homeCoordinates": {
              "latitude": 47.6760654544942,
              "longitude": -122.318150997162
            }
          }');
        $userResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $userResponse->shouldReceive('getStatusCode')->andReturn(200);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(2)
            ->andReturn($postResponse, $userResponse);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $user = $this->provider->getResourceOwner($token);

        $this->assertEquals($userId, $user->getId());
        $this->assertEquals($userId, $user->toArray()['referenceCode']);
        $this->assertEquals($username, $user->getUsername());
        $this->assertEquals($username, $user->toArray()['username']);
    }

    public function testExceptionThrownWhenErrorObjectReceived()
    {
        $status = rand(400, 600);
        $postResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $postResponse->shouldReceive('getBody')->andReturn('{"message": "Validation Failed","errors": [{"resource": "Issue","field": "title","code": "missing_field"}]}');
        $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $postResponse->shouldReceive('getStatusCode')->andReturn($status);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(1)
            ->andReturn($postResponse);
        $this->provider->setHttpClient($client);

        $this->expectException(\League\OAuth2\Client\Provider\Exception\IdentityProviderException::class);
        $this->expectExceptionMessage('Validation Failed');

        $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
    }

    public function testExceptionThrownWhenOAuthErrorReceived()
    {
        $status = 200;
        $postResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $postResponse->shouldReceive('getBody')->andReturn('{"error": "bad_verification_code","error_description": "The code passed is incorrect or expired.","error_uri": "https://api.groundspeak.com/documentation"}');
        $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $postResponse->shouldReceive('getStatusCode')->andReturn($status);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(1)
            ->andReturn($postResponse);
        $this->provider->setHttpClient($client);

        $this->expectException(\League\OAuth2\Client\Provider\Exception\IdentityProviderException::class);
        $this->expectExceptionMessage('bad_verification_code');

        $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
    }
}
