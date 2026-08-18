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

    protected string $environment = 'production';

    public string $domain;

    public string $apiDomain;

    public string $oAuthDomain;

    protected $clientId;

    protected $clientSecret;

    protected $redirectUri;

    public string $scope = GeocachingConfig::DEFAULT_SCOPE;

    public string $pkceMethod = GeocachingConfig::DEFAULT_PKCE_METHOD;

    private string $responseResourceOwnerId = GeocachingConfig::RESPONSE_RESOURCE_OWNER_ID;

    private array $resourceOwnerFields;

    /**
     * Constructs an OAuth 2.0 service provider for Geocaching.
     *
     * @param array $options An array of options to set on this provider.
     *     Standard options: `clientId`, `clientSecret`, `redirectUri`, `environment`.
     *     Custom URL options: `domain`, `apiDomain`, `oAuthDomain` (override environment defaults).
     * @param array $collaborators An array of collaborators that may be used to
     *     override this provider's default behavior. Collaborators include
     *     `grantFactory`, `requestFactory`, and `httpClient`.
     *
     * @example
     * ```php
     * // Standard usage with predefined environment
     * $provider = new Geocaching([
     *     'clientId' => 'your-client-id',
     *     'clientSecret' => 'your-client-secret',
     *     'environment' => 'staging',
     *     'redirectUri' => 'https://app.example.com/callback'
     * ]);
     *
     * // Custom URLs for development
     * $provider = new Geocaching([
     *     'clientId' => 'dev-client-id',
     *     'clientSecret' => 'dev-client-secret',
     *     'environment' => 'dev',
     *     'redirectUri' => 'http://localhost:3000/callback',
     *     'domain' => 'https://my-geocaching.local',
     *     'apiDomain' => 'https://api.my-geocaching.local'
     * ]);
     * ```
     */
    public function __construct(array $options = [], array $collaborators = [])
    {
        $this->assertRequiredOptions($options);
        parent::__construct($options, $collaborators);

        // Get environment defaults
        $config = GeocachingConfig::getEnvironmentConfig($this->environment);

        // Allow custom URL overrides, fallback to environment defaults
        $this->domain = $options['domain'] ?? $config['domain'];
        $this->apiDomain = $options['apiDomain'] ?? $config['apiDomain'];
        $this->oAuthDomain = $options['oAuthDomain'] ?? $config['oAuthDomain'];
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
            'domain',         // Allow custom domain override
            'apiDomain',      // Allow custom API domain override
            'oAuthDomain',    // Allow custom OAuth domain override
        ]);
    }

    /**
     * Returns all options that are required.
     *
     * @return array
     */
    protected function getRequiredOptions(): array
    {
        return [
            'clientId',
            'clientSecret',
            'redirectUri',
            'environment',
        ];
    }

    private function assertRequiredOptions(array $options): void
    {
        $missing = array_diff($this->getRequiredOptions(), array_keys($options));

        if ($missing) {
            throw new InvalidArgumentException('Required options not defined: ' . implode(', ', $missing));
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
        return $this->resourceOwnerFields ?? GeocachingConfig::DEFAULT_RESOURCE_OWNER_FIELDS;
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
