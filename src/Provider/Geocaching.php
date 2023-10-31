<?php

namespace League\OAuth2\Client\Provider;

use InvalidArgumentException;
use League\OAuth2\Client\Provider\Exception\GeocachingIdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class Geocaching extends AbstractProvider
{
    use BearerAuthorizationTrait;

    public const DEV_DOMAIN = 'http://localhost:8000';
    public const DEV_API_DOMAIN = 'http://localhost:8000';

    public const PRODUCTION_DOMAIN = 'https://www.geocaching.com';
    public const STAGING_DOMAIN = 'https://staging.geocaching.com';

    public const PRODUCTION_OAUTH_DOMAIN = 'https://oauth.geocaching.com';
    public const STAGING_OAUTH_DOMAIN = 'https://oauth-staging.geocaching.com';

    public const PRODUCTION_API_DOMAIN = 'https://api.groundspeak.com';
    public const STAGING_API_DOMAIN = 'https://staging.api.groundspeak.com';

    protected $environment = 'production';

    public $domain;

    public $apiDomain;

    public $oAuthDomain;

    public $clientId;

    public $clientSecret;

    public $redirectUri;

    public $scope = '*';

    public $pkceMethod = 'S256';

    private $responseResourceOwnerId = 'referenceCode';

    private $resourceOwnerFieldsDefault = [
        'referenceCode',
        'findCount',
        'hideCount',
        'favoritePoints',
        'username',
        'membershipLevelId',
        'joinedDateUtc',
    ];

    private $resourceOwnerFields;

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
        $this->assertRequiredOptions($options);

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
     * Returns all options that can be configured.
     *
     * @return array
     */
    protected function getConfigurableOptions(): array
    {
        return array_merge($this->getRequiredOptions(), [
            'clientId',
            'clientSecret',
            'redirectUri',
            'environment',
        ]);
    }

    /**
     * Returns all options that are required.
     *
     * @return array
     */
    protected function getRequiredOptions()
    {
        return [
            'clientId',
            'clientSecret',
            'redirectUri',
            'environment',
        ];
    }

    private function assertRequiredOptions(array $options)
    {
        $missing = array_diff_key(array_flip($this->getRequiredOptions()), $options);

        if (!empty($missing)) {
            throw new InvalidArgumentException(
                'Required options not defined: ' . implode(', ', array_keys($missing))
            );
        }
    }

    /**
     * Get authorization url to begin OAuth flow
     *
     * @return string
     */
    public function getBaseAuthorizationUrl(): string
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
    public function getBaseAccessTokenUrl(array $params): string
    {
        return $this->oAuthDomain . '/token';
    }

    /**
     * Get provider url to fetch user details
     *
     * @param  AccessToken $token
     * @return string
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        $query = ['fields' => implode(',', $this->getResourceOwnerFields()),
                ];
        return $this->apiDomain . '/v1/users/me?' . http_build_query($query);
    }

    public function getDefaultScopes(): array
    {
        return [$this->scope];
    }

    /**
     * @inheritdoc
     */
    protected function getPkceMethod(): string
    {
        return $this->pkceMethod;
    }

    /**
     * Check a provider response for errors.
     *
     * @link   https://api.groundspeak.com/documentation#responses
     * @throws GeocachingIdentityProviderException
     * @param  ResponseInterface $response
     * @param  array|string $data Parsed response data
     * @return void
     */
    protected function checkResponse(ResponseInterface $response, $data): void
    {
        if (isset($data['error'])) {
            throw GeocachingIdentityProviderException::oauthException($response, $data);
        }
        if ($response->getStatusCode() >= 400) {
            throw GeocachingIdentityProviderException::clientException($response, $data);
        }
    }

    /**
     * @inheritdoc
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new GeocachingResourceOwner($response, $this->responseResourceOwnerId);
    }

    public function getResourceOwnerFields(): array
    {
        return $this->resourceOwnerFields ?? $this->resourceOwnerFieldsDefault;
    }

    /**
     * Set the owner fields to retrieve from https://api.groundspeak.com/documentation#user
     */
    public function setResourceOwnerFields(array $resourceOwnerFields): self
    {
        $this->resourceOwnerFields = $resourceOwnerFields;

        return $this;
    }
}
