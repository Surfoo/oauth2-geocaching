<?php

namespace League\OAuth2\Client\Provider;

use League\OAuth2\Client\Tool\ArrayAccessorTrait;

class GeocachingResourceOwner implements ResourceOwnerInterface
{
    use ArrayAccessorTrait;

    /**
     * Domain
     */
    protected string $domain;

    /**
     * Creates new resource owner.
     */
    public function __construct(protected array $response = [])
    {
        $this->response = $response;
    }

    /**
     * Get resource owner reference code
     *
     * @return string|null
     */
    public function getId()
    {
        return $this->getReferenceCode();
    }

    /**
     * Get resource owner reference code
     *
     * @return string|null
     */
    public function getReferenceCode()
    {
        return $this->getValueByKey($this->response, 'referenceCode');
    }

    /**
     * Get resource owner find count
     *
     * @return string|null
     */
    public function getFindCount()
    {
        return $this->getValueByKey($this->response, 'findCount');
    }

    /**
     * Get resource owner hide count
     *
     * @return string|null
     */
    public function getHideCount()
    {
        return $this->getValueByKey($this->response, 'hideCount');
    }

    /**
     * Get resource owner favorite points
     *
     * @return string|null
     */
    public function getFavoritePoints()
    {
        return $this->getValueByKey($this->response, 'favoritePoints');
    }

    /**
     * Get resource owner username
     *
     * @return string|null
     */
    public function getUsername()
    {
        return $this->getValueByKey($this->response, 'username');
    }

    /**
     * Get resource owner membership Level Id
     *
     * @return string|null
     */
    public function getMembershipLevelId()
    {
        return $this->getValueByKey($this->response, 'membershipLevelId');
    }

    /**
     * Get resource owner joined date UTC
     *
     * @return string|null
     */
    public function getJoinedDate()
    {
        return $this->getValueByKey($this->response, 'joinedDateUtc');
    }

    /**
     * Get resource owner avatar url
     *
     * @return string|null
     */
    public function getAvatarUrl()
    {
        return $this->getValueByKey($this->response, 'avatarUrl');
    }

    /**
     * Get resource owner banner url
     *
     * @return string|null
     */
    public function getBannerUrl()
    {
        return $this->getValueByKey($this->response, 'bannerUrl');
    }

    /**
     * Get resource profile url
     *
     * @return string|null
     */
    public function getProfileUrl()
    {
        return $this->getValueByKey($this->response, 'url');
    }

    /**
     * Get resource profile text
     *
     * @return string|null
     */
    public function getProfileText()
    {
        return $this->getValueByKey($this->response, 'profileText');
    }

    /**
     * Get resource owner home coordinates
     *
     * @return string|null
     */
    public function getHomeCoordinates()
    {
        return $this->getValueByKey($this->response, 'homeCoordinates');
    }

    /**
     * Get resource owner opt-in friend sharing
     *
     * @return bool
     */
    public function getOptedInFriendSharing()
    {
        return (bool) $this->getValueByKey($this->response, 'optedInFriendSharing');
    }

    /**
    * Get resource owner geocache limits
    *
    * @return string|null
    */
    public function getGeocacheLimits()
    {
        return $this->getValueByKey($this->response, 'geocacheLimits');
    }

    /**
     * Set resource owner domain
     *
     * @return ResourceOwner
     */
    public function setDomain(string $domain)
    {
        $this->domain = $domain;

        return $this;
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
