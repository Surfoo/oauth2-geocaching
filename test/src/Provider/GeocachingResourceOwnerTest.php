<?php namespace League\OAuth2\Client\Test\Provider;

use Mockery as m;
use \League\OAuth2\Client\Provider\GeocachingResourceOwner;

class GeocachingResourceOwnerTest extends \PHPUnit\Framework\TestCase
{
    protected $provider;

    /**
     * user data
     *
     * @var array
     */
    protected $user;

    protected function setUp(): void
    {
        $this->provider = new \League\OAuth2\Client\Provider\Geocaching([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'none',
        ]);


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
            "hideCount": 123,
            "favoritePoints": 18,
            "username": "MNofMind",
            "membershipLevelId": 3,
            "avatarUrl": "https://img-stage.geocaching.com/gcstage/{0}/0640f488-9abe-4c2a-a786-bb75cec84357.gif",
            "homeCoordinates": {
                "latitude": 47.6760654544942,
                "longitude": -122.318150997162
            },
            "geocacheLimits" : {
                "liteCallsRemaining": 10000,
                "liteCallsSecondsToLive": null,
                "fullCallsRemaining": 2,
                "fullCallsSecondsToLive": 216000
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
        $this->user = $this->provider->getResourceOwner($token);
    }
    
    public function testGetId()
    {
        $this->assertEquals('PR27A92', $this->user->getId());
    }

    public function testGetReferenceCode()
    {
        $this->assertEquals('PR27A92', $this->user->getReferenceCode());
    }

    public function testGetFindCount()
    {
        $this->assertEquals(326, $this->user->getFindCount());
    }
 
    public function testGetHideCount()
    {
        $this->assertEquals(123, $this->user->getHideCount());
    }
  
    public function testGetFavortePoints()
    {
        $this->assertEquals(18, $this->user->getFavoritePoints());
    }

    public function testGetUsername()
    {
        $this->assertEquals('MNofMind', $this->user->getUsername());
    }

    public function testGetMembershipLevelId()
    {
        $this->assertEquals(3, $this->user->getMembershipLevelId());
    }

    public function testGetAvatarUrl()
    {
        $this->assertEquals('https://img-stage.geocaching.com/gcstage/{0}/0640f488-9abe-4c2a-a786-bb75cec84357.gif', $this->user->getAvatarUrl());
    }

    public function testGetHomeCoordinates()
    {
        $this->assertEquals(['latitude' => 47.6760654544942, 'longitude' => -122.318150997162], $this->user->getHomeCoordinates());
    }
    
    public function testGetGeocacheLimits()
    {
        $this->assertEquals([
            "liteCallsRemaining" => 10000,
            "liteCallsSecondsToLive" => null,
            "fullCallsRemaining" => 2,
            "fullCallsSecondsToLive" => 216000
        ], $this->user->getGeocacheLimits());
    }

    public function testToArray()
    {
        $this->assertEquals([
            "referenceCode" => "PR27A92",
            "findCount" => 326,
            "hideCount" => 123,
            "favoritePoints" => 18,
            "username" => "MNofMind",
            "membershipLevelId" => 3,
            "avatarUrl" => "https://img-stage.geocaching.com/gcstage/{0}/0640f488-9abe-4c2a-a786-bb75cec84357.gif",
            "homeCoordinates" => [
                "latitude" => 47.6760654544942,
                "longitude" => -122.318150997162
            ],
            "geocacheLimits"  => [
                "liteCallsRemaining" => 10000,
                "liteCallsSecondsToLive" => null,
                "fullCallsRemaining" => 2,
                "fullCallsSecondsToLive" => 216000
            ]
        ], $this->user->toArray());
    }
}
