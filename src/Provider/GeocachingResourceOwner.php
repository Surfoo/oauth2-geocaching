<?php

namespace League\OAuth2\Client\Provider;

use League\OAuth2\Client\Tool\ArrayAccessorTrait;

class GeocachingResourceOwner implements ResourceOwnerInterface
{

    /**
     * @var array
     */
    protected $response;

    /**
     * Domain
     */
    protected string $domain;

    /**
     * @param string $resourceOwnerId
     */
    public function __construct(array $response, protected $resourceOwnerId)
    {
        $this->response = $response;
    }

    /**
     * Returns the identifier of the authorized resource owner.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->response[$this->resourceOwnerId];
    }

    /**
     * Get resource owner reference code
     *
     * @return string|null
     */
    public function getReferenceCode()
    {
        return $this->response['referenceCode'];
    }

    /**
     * Get resource owner find count
     *
     * @return string|null
     */
    public function getFindCount()
    {
        return $this->response['findCount'];
    }

    /**
     * Get resource owner hide count
     *
     * @return string|null
     */
    public function getHideCount()
    {
        return $this->response['hideCount'];
    }

    /**
     * Get resource owner favorite points
     *
     * @return string|null
     */
    public function getFavoritePoints()
    {
        return $this->response['favoritePoints'];
    }

    /**
     * Get resource owner username
     *
     * @return string|null
     */
    public function getUsername()
    {
        return $this->response['username'];
    }

    /**
     * Get resource owner membership Level Id
     *
     * @return string|null
     */
    public function getMembershipLevelId()
    {
        return $this->response['membershipLevelId'];
    }

    /**
     * Get resource owner joined date UTC
     *
     * @return string|null
     */
    public function getJoinedDate()
    {
        return $this->response['joinedDateUtc'];
    }

    /**
     * Get resource owner avatar url
     *
     * @return string|null
     */
    public function getAvatarUrl()
    {
        return $this->response['avatarUrl'];
    }

    /**
     * Get resource owner banner url
     *
     * @return string|null
     */
    public function getBannerUrl()
    {
        return $this->response['bannerUrl'];
    }

    /**
     * Get resource profile url
     *
     * @return string|null
     */
    public function getProfileUrl()
    {
        return $this->response['url'];
    }

    /**
     * Get resource profile text
     *
     * @return string|null
     */
    public function getProfileText()
    {
        return $this->response['profileText'];
    }

    /**
     * Get resource owner home coordinates
     *
     * @return string|null
     */
    public function getHomeCoordinates()
    {
        return $this->response['homeCoordinates'];
    }

    /**
     * Get resource owner opt-in friend sharing
     *
     * @return bool
     */
    public function getOptedInFriendSharing()
    {
        return (bool) $this->response['optedInFriendSharing'];
    }

    /**
    * Get resource owner geocache limits
    *
    * @return string|null
    */
    public function getGeocacheLimits()
    {
        return $this->response['geocacheLimits'];
    }

    /**
     * Return all of the owner details available as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->response;
    }
}
