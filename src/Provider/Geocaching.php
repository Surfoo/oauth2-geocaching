<?php

namespace League\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\Exception\GeocachingIdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class Geocaching extends AbstractProvider
{
    use BearerAuthorizationTrait;

    const DEV_DOMAIN = 'http://localhost:8000';
    const DEV_API_DOMAIN = 'http://localhost:8000';

    const PRODUCTION_DOMAIN = 'https://www.geocaching.com';
    const STAGING_DOMAIN = 'https://staging.geocaching.com';

    const PRODUCTION_OAUTH_DOMAIN = 'https://oauth.geocaching.com';
    const STAGING_OAUTH_DOMAIN = 'https://oauth-staging.geocaching.com';

    const PRODUCTION_API_DOMAIN = 'https://api.groundspeak.com';
    const STAGING_API_DOMAIN = 'https://staging.api.groundspeak.com';

    protected $environment = 'production';

    /**
     * Main domain
     *
     * @var string
     */
    public $domain;

    /**
     * Api domain
     *
     * @var string
     */
    public $apiDomain;

    /**
     * OAuth domain
     *
     * @var string
     */
    public $oAuthDomain;

    /**
     * Constructs an OAuth 2.0 service provider.oAuthDomain
     *
     * @param array $options An array of options to set on this provider.
     *     Options include `clientId`, `clientSecret`, `redirectUri`, and `state`.
     *     Individual providers may introduce more options, as needed.
     * @param array $collaborators An array of collaborators that may be used to
     *     override this provider's default behavior. Collaborators include
     *     `grantFactory`, `requestFactory`, and `httpClient`.
     *     Individual providers may introduce more collaborators, as needed.
     */
    public function __construct(array $options = [], array $collaborators = [])
    {
        parent::__construct($options, $collaborators);

        switch ($this->environment) {
            case 'dev':
            case 'development':
            case 'docker':
                $this->domain = self::DEV_DOMAIN;
                $this->apiDomain = self::DEV_DOMAIN;
                $this->oAuthDomain = self::DEV_DOMAIN;
                break;
            case 'staging':
            case 'qa':
                $this->domain = self::STAGING_DOMAIN;
                $this->apiDomain = self::STAGING_API_DOMAIN;
                $this->oAuthDomain = self::STAGING_OAUTH_DOMAIN;
                break;
            case 'production':
            case 'prod':
            default:
                $this->domain = self::PRODUCTION_DOMAIN;
                $this->apiDomain = self::PRODUCTION_API_DOMAIN;
                $this->oAuthDomain = self::PRODUCTION_OAUTH_DOMAIN;
        }
    }

    /**
     * Get authorization url to begin OAuth flow
     *
     * @return string
     */
    public function getBaseAuthorizationUrl()
    {
        return $this->domain . '/oauth/authorize.aspx';
    }

    /**
     * Get access token url to retrieve token
     *
     * @param  array $params
     *
     * @return string
     */
    public function getBaseAccessTokenUrl(array $params)
    {
        return $this->oAuthDomain . '/token';
    }

    /**
     * Get provider url to fetch user details
     *
     * @param  AccessToken $token
     * @return string
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return $this->apiDomain . '/v1/users/me';
    }

    /**
     * Get the default scopes used by this provider.
     *
     * This should not be a complete list of all scopes, but the minimum
     * required for the provider user interface!
     *
     * @return array
     */
    protected function getDefaultScopes()
    {
        return [];
    }

    /**
     * Check a provider response for errors.
     *
     * @link   https://api.groundspeak.com/documentation#responses
     * @throws IdentityProviderException
     * @param  ResponseInterface $response
     * @param  array $data Parsed response data
     * @return void
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        if ($response->getStatusCode() >= 400) {
            throw GeocachingIdentityProviderException::clientException($response, $data);
        } elseif (isset($data['error'])) {
            throw GeocachingIdentityProviderException::oauthException($response, $data);
        }
    }

    /**
     * Generate a user object from a successful user details request.
     *
     * @param array $response
     * @param AccessToken $token
     * @return \League\OAuth2\Client\Provider\ResourceOwnerInterface
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        $user = new GeocachingResourceOwner($response);

        return $user->setDomain($this->domain);
    }
}
