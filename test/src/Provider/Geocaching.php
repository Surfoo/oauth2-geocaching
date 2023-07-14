<?php

namespace League\OAuth2\Client\Test\Provider;

use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Provider\Geocaching as GeocachingProvider;

class Geocaching extends GeocachingProvider
{
    public function __construct($options = [], array $collaborators = [])
    {
        // Add the required defaults for AbstractProvider
        $options += [
            'clientId'     => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri'  => 'none',
        ];

        parent::__construct($options);
    }

    protected function fetchResourceOwnerDetails(AccessToken $token)
    {
        return [
            'referenceCode'     => 'PR1QQQP',
            'username'          => 'testmock',
            'joinedDateUtc'     => '2000-12-31T10:10:10.123',
            'favoritePoints'    => 100,
            'membershipLevelId' => 3,
            'findCount'         => 42,
            'hideCount'         => 24,
            'avatarUrl'         => 'https://img.geocaching.com/large/avatar.jpg',
            'bannerUrl'         => 'https://www.geocaching.com/account/app/ui-images/components/profile/p_bgimage-large.png',
            'url'               => 'https://coord.info/PR1QQQP',
            'profileText'       => 'lorem lipsum',
            'homeCoordinates'   => [
                "latitude"  => 47.6760654544942,
                "longitude" => -122.318150997162
            ],
            'optedInFriendSharing' => true,
            'geocacheLimits' => [
                
            ]
        ];
    }
}
