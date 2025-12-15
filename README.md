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

Take a look at `demo/index.php`

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
