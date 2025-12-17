# Geocaching Provider for OAuth 2.0 Client
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Total Downloads](https://img.shields.io/packagist/dt/surfoo/oauth2-geocaching.svg?style=flat-square)](https://packagist.org/packages/Surfoo/oauth2-geocaching)

This package provides Geocaching OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

## Installation

To install, use composer:

```
composer require surfoo/oauth2-geocaching
```

## Usage

Usage is the same as The League's OAuth client, using `\League\OAuth2\Client\Provider\Geocaching` as the provider.

### Authorization Code Flow

```php
use League\OAuth2\Client\Provider\Geocaching;

$provider = new Geocaching([
    'clientId'     => 'your-client-id',
    'clientSecret' => 'your-client-secret',
    'redirectUri'  => 'https://your-app.com/callback',
    'environment'  => 'production', // 'dev', 'staging', 'production'
]);

// Get authorization URL
$authUrl = $provider->getAuthorizationUrl(['scope' => '*']);
$_SESSION['oauth2state'] = $provider->getState();

// Redirect user to Geocaching
header('Location: ' . $authUrl);
exit;

// In your callback handler
if (!empty($_GET['code'])) {
    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);

    $user = $provider->getResourceOwner($token);
    echo 'Hello ' . $user->getUsername() . '!';
}
```

### Environments

| Environment | Description | URLs |
|------------|-------------|------|
| `production`, `prod` | Official Geocaching.com | `geocaching.com`, `api.groundspeak.com` |
| `staging`, `qa` | Staging environment | `staging.geocaching.com` |
| `dev`, `development`, `docker`, `test` | Local development | `localhost:8000` |

### Custom URLs for Development

You can override environment URLs for your own development infrastructure:

```php
use League\OAuth2\Client\Provider\Geocaching;
use League\OAuth2\Client\Provider\GeocachingConfig;

// Method 1: Direct URL overrides
$provider = new Geocaching([
    'clientId' => 'dev-client-id',
    'clientSecret' => 'dev-secret',
    'environment' => 'dev',
    'redirectUri' => 'http://localhost:3000/callback',

    // Custom URLs (override environment defaults)
    'domain' => 'https://my-geocaching.local',
    'apiDomain' => 'https://api.my-geocaching.local',
    'oAuthDomain' => 'https://oauth.my-geocaching.local',
]);

// Method 2: Using configuration helper
$config = GeocachingConfig::create('staging', [
    'apiDomain' => 'https://my-internal-api.company.com'
]);

$provider = new Geocaching(array_merge([
    'clientId' => 'client-id',
    'clientSecret' => 'client-secret',
    'redirectUri' => 'https://app.company.com/callback',
], $config));

// Method 3: Factory helpers for common patterns
use League\OAuth2\Client\Test\Factory\GeocachingTestFactory;

$provider = GeocachingTestFactory::createForDocker(9000);
// or
$provider = GeocachingTestFactory::createForLocalDev('http://192.168.1.100:8080');
```

#### Factory Helpers for Common Patterns

```php
use League\OAuth2\Client\Test\Factory\GeocachingTestFactory;

// Docker with custom port
$provider = GeocachingTestFactory::createForDocker(9000);
// Results in: http://localhost:9000, http://localhost:9000/api, http://localhost:9000/oauth

// Local development with custom base URL
$provider = GeocachingTestFactory::createForLocalDev('http://192.168.1.100:8080');
// Results in: http://192.168.1.100:8080, http://192.168.1.100:8080/api/v1, http://192.168.1.100:8080/oauth

// Completely custom URLs
$provider = GeocachingTestFactory::createWithCustomUrls(
    'https://geocaching.mycompany.local',
    'https://api.geocaching.mycompany.local',
    'https://oauth.geocaching.mycompany.local'
);
```

#### Advanced Configuration Examples

**Docker Compose Setup:**
```php
$provider = new Geocaching([
    'clientId' => $_ENV['GEOCACHING_CLIENT_ID'],
    'clientSecret' => $_ENV['GEOCACHING_CLIENT_SECRET'],
    'environment' => 'dev',
    'redirectUri' => 'http://localhost:3000/auth/callback',

    'domain' => 'http://geocaching-web:80',
    'apiDomain' => 'http://geocaching-api:8080/v1',
    'oAuthDomain' => 'http://geocaching-oauth:9090',
]);
```

**Enterprise Infrastructure:**
```php
$config = GeocachingConfig::create('production', [
    'domain' => 'https://geocaching.internal.company.com',
    'apiDomain' => 'https://geocaching-api.internal.company.com/v2',
    'oAuthDomain' => 'https://sso.company.com/geocaching',
]);

$provider = new Geocaching(array_merge([
    'clientId' => $_ENV['COMPANY_GEOCACHING_CLIENT_ID'],
    'clientSecret' => $_ENV['COMPANY_GEOCACHING_CLIENT_SECRET'],
    'redirectUri' => 'https://myapp.company.com/oauth/callback',
], $config));
```

**Multi-Environment Deployment:**
```php
$environment = $_ENV['APP_ENV'] ?? 'production';

switch ($environment) {
    case 'local':
        $provider = GeocachingTestFactory::createForLocalDev($_ENV['DEV_BASE_URL']);
        break;
    case 'staging':
        $config = GeocachingConfig::create('staging', [
            'apiDomain' => $_ENV['STAGING_API_URL'],
        ]);
        $provider = new Geocaching(array_merge($baseOptions, $config));
        break;
    case 'production':
    default:
        $provider = new Geocaching(array_merge($baseOptions, [
            'environment' => 'production'
        ]));
        break;
}
```

Take a look at `demo/index.php` for complete examples.

### Resource Owner (User Data)

After obtaining an access token, you can retrieve user information:

```php
$user = $provider->getResourceOwner($token);

// Basic information
echo $user->getReferenceCode();    // 'PR1ABC2'
echo $user->getUsername();         // 'MyUsername'
echo $user->getFindCount();        // 150
echo $user->getHideCount();        // 5
echo $user->getFavoritePoints();   // 25

// Profile information
echo $user->getMembershipLevelId(); // 3 (Premium membership)
echo $user->getJoinedDate();       // '2020-01-15T10:30:00.123'
echo $user->getAvatarUrl();        // URL to user's avatar image
echo $user->getProfileUrl();       // URL to user's public profile
echo $user->getProfileText();      // User's profile description

// Additional data
$coordinates = $user->getHomeCoordinates(); // Array with lat/lon
$geocacheLimits = $user->getGeocacheLimits(); // API usage limits
$friendSharing = $user->getOptedInFriendSharing(); // Privacy setting
```

#### Custom Resource Owner Fields

By default, the provider requests a standard set of user fields. You can customize which fields to retrieve:

```php
$provider->setResourceOwnerFields([
    'referenceCode',
    'username',
    'findCount',
    'hideCount',
    'favoritePoints',
    'membershipLevelId',
    'joinedDateUtc',
    'avatarUrl',
    'profileText'
    // Add any other fields supported by the Geocaching API
]);

$user = $provider->getResourceOwner($token);
// Now only the specified fields will be requested from the API
```

### Token Management & Refresh

This package ships token lifecycle utilities you can plug into any PSR-18 client:

- `TokenSet`: lightweight DTO for access/refresh tokens with expiry helpers.
- `TokenStorageInterface`: implement to persist tokens (DB, cache, file) with locking.
- `TokenRefreshPlugin`: HTTPlug/PSR plugin that refreshes tokens on `401` and retries the original request.
- Exceptions for refresh/storage errors: `TokenRefreshException`, `RefreshTokenExpiredException`, `TokenStorageException`.

Basic wiring:

```php
use Http\Client\Common\PluginClientFactory;
use League\OAuth2\Client\Plugin\TokenRefreshPlugin;
use League\OAuth2\Client\Provider\Geocaching;
use League\OAuth2\Client\Token\TokenStorageInterface;
use League\OAuth2\Client\Token\TokenSet;
use Nyholm\Psr7\Request;
use Psr\Log\NullLogger;

$provider = new Geocaching([
    'clientId'     => 'client_id',
    'clientSecret' => 'client_secret',
    'redirectUri'  => 'https://your-app.test/callback',
    'environment'  => 'production', // or staging/dev
]);

$storage = new class implements TokenStorageInterface {
    private ?TokenSet $tokens = null;
    public function getTokens(string $referenceCode): ?TokenSet { return $this->tokens; }
    public function saveTokens(string $referenceCode, TokenSet $tokens): void { $this->tokens = $tokens; }
    public function lockUser(string $referenceCode, int $timeoutSeconds = 30): bool { return true; }
    public function unlockUser(string $referenceCode): void {}
    public function isUserLocked(string $referenceCode): bool { return false; }
};

$refreshPlugin = new TokenRefreshPlugin(
    referenceCode: 'PR12345',
    storage: $storage,
    oauthProvider: $provider,
    logger: new NullLogger(),
    maxRetryAttempts: 3
);

$httpClient = (new PluginClientFactory())->createClient(
    \Http\Discovery\Psr18ClientDiscovery::find(),
    [$refreshPlugin]
);

$request = new Request('GET', $provider->apiDomain . '/v1/users/PR12345');
$response = $httpClient->sendRequest($request);
```

See `demo/index.php` for a full flow including PKCE, token storage, and a sample API call with automatic refresh. In production, replace the in-memory storage with a durable implementation.

## Testing

``` bash
$ ./vendor/bin/phpunit
```

## License

The MIT License (MIT). Please see [License File](https://github.com/Surfoo/oauth2-geocaching/blob/master/LICENSE) for more information.
