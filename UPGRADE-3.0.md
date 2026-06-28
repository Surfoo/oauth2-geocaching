# Upgrading from 2.1.0 to 3.0.0

## Requirements

- PHP **8.2** or higher (raised from 8.1)
- `league/oauth2-client` **^2.9** (raised from ^2.7)

New required packages — install them via Composer:

```bash
composer require php-http/client-common:^2.7 php-http/promise:^1.1 psr/log:^3.0
```

Or update your `composer.json` require section and run `composer update`.

---

## Breaking Changes

### PHP 8.1 dropped

PHP 8.1 is no longer supported. Update your runtime to PHP 8.2+.

### `environment` option is now required

In 2.x the `environment` key had an implicit `'production'` default. In 3.0 it is listed as a required option and must be provided explicitly.

**Before:**
```php
$provider = new Geocaching([
    'clientId'     => 'your-client-id',
    'clientSecret' => 'your-client-secret',
    'redirectUri'  => 'https://example.com/callback',
]);
```

**After:**
```php
$provider = new Geocaching([
    'clientId'     => 'your-client-id',
    'clientSecret' => 'your-client-secret',
    'redirectUri'  => 'https://example.com/callback',
    'environment'  => 'production',
]);
```

---

## New Features

The sections below describe what 3.0 adds. Nothing you already use has been removed.

### Custom URL overrides on the provider

You can now pass `domain`, `apiDomain`, and `oAuthDomain` directly to the `Geocaching` constructor to override the defaults for any environment. This is useful for Docker setups, corporate proxies, or internal staging infrastructure.

```php
$provider = new Geocaching([
    'clientId'     => 'your-client-id',
    'clientSecret' => 'your-client-secret',
    'redirectUri'  => 'http://localhost:3000/callback',
    'environment'  => 'dev',
    'domain'       => 'http://geocaching-web:80',
    'apiDomain'    => 'http://geocaching-api:8080/v1',
    'oAuthDomain'  => 'http://geocaching-oauth:9090',
]);
```

### `GeocachingConfig` helper

`GeocachingConfig::create()` builds a configuration array from a base environment plus selective overrides, which you can spread into the provider constructor.

```php
use League\OAuth2\Client\Provider\GeocachingConfig;

$config = GeocachingConfig::create('staging', [
    'apiDomain' => 'https://my-internal-api.company.com',
]);

$provider = new Geocaching(array_merge([
    'clientId'     => 'your-client-id',
    'clientSecret' => 'your-client-secret',
    'redirectUri'  => 'https://app.company.com/callback',
], $config));
```

Valid environment aliases: `dev`, `development`, `docker`, `test`, `staging`, `qa`, `production`, `prod`.

### Token management

Three new classes handle the full token lifecycle:

#### `TokenSet`

A read-only DTO holding an access token, refresh token, and expiry:

```php
use League\OAuth2\Client\Token\TokenSet;

// Create from an OAuth response array
$tokens = TokenSet::fromOAuthResponse($oauthResponseArray, $refreshToken);

// Or create directly with an explicit TTL
$tokens = TokenSet::create($accessToken, $refreshToken, expiresInSeconds: 3600);

// Check expiry (with a 60-second safety buffer by default)
if ($tokens->isExpired()) { /* refresh */ }
if ($tokens->isExpired(bufferSeconds: 120)) { /* refresh earlier */ }

// Serialize for storage
$row = $tokens->toArray();

// Restore from storage
$tokens = TokenSet::fromArray($row);

// Build an Authorization header manually
$header = $tokens->getAuthorizationHeader(); // "Bearer <token>"
```

#### `TokenStorageInterface`

Implement this interface to persist tokens in your storage backend (database, Redis, file, etc.):

```php
use League\OAuth2\Client\Token\TokenSet;
use League\OAuth2\Client\Token\TokenStorageInterface;

class MyTokenStorage implements TokenStorageInterface
{
    public function getTokens(string $referenceCode): ?TokenSet
    {
        $row = $this->db->find($referenceCode);
        return $row ? TokenSet::fromArray($row) : null;
    }

    public function saveTokens(string $referenceCode, TokenSet $tokens): void
    {
        $this->db->upsert($referenceCode, $tokens->toArray());
    }

    public function lockUser(string $referenceCode, int $timeoutSeconds = 30): bool
    {
        return $this->cache->add("lock:{$referenceCode}", 1, $timeoutSeconds);
    }

    public function unlockUser(string $referenceCode): void
    {
        $this->cache->delete("lock:{$referenceCode}");
    }

    public function isUserLocked(string $referenceCode): bool
    {
        return $this->cache->has("lock:{$referenceCode}");
    }
}
```

The locking methods (`lockUser`, `unlockUser`, `isUserLocked`) protect against concurrent token refreshes. If your application is single-process you can return `true`/no-op from those methods.

#### `TokenRefreshPlugin`

An HTTPlug plugin that intercepts `401` responses, refreshes the access token, and retries the original request automatically:

```php
use Http\Client\Common\PluginClientFactory;
use Http\Discovery\Psr18ClientDiscovery;
use League\OAuth2\Client\Plugin\TokenRefreshPlugin;
use Psr\Log\NullLogger;

$plugin = new TokenRefreshPlugin(
    referenceCode:    'PR12345',
    storage:          $myTokenStorage,
    oauthProvider:    $provider,
    logger:           new NullLogger(), // optional, defaults to NullLogger
    maxRetryAttempts: 3,                // optional, defaults to 3
);

$httpClient = (new PluginClientFactory())->createClient(
    Psr18ClientDiscovery::find(),
    [$plugin]
);

// All requests through $httpClient now auto-refresh on 401
$response = $httpClient->sendRequest($request);
```

### New exceptions

Three exceptions are thrown by `TokenRefreshPlugin` and can be caught individually:

| Exception | When thrown |
|-----------|-------------|
| `League\OAuth2\Client\Exception\TokenRefreshException` | The refresh request itself failed (network error, provider error, etc.) |
| `League\OAuth2\Client\Exception\RefreshTokenExpiredException` | The refresh token is invalid or expired — the user must re-authenticate. Extends `TokenRefreshException`. |
| `League\OAuth2\Client\Exception\TokenStorageException` | Could not read/write from storage or failed to acquire the concurrency lock. |

```php
use League\OAuth2\Client\Exception\RefreshTokenExpiredException;
use League\OAuth2\Client\Exception\TokenRefreshException;
use League\OAuth2\Client\Exception\TokenStorageException;

try {
    $response = $httpClient->sendRequest($request);
} catch (RefreshTokenExpiredException $e) {
    // Redirect the user to re-authenticate
} catch (TokenRefreshException $e) {
    // Transient refresh failure; inspect $e->getResponseData() for details
} catch (TokenStorageException $e) {
    // Storage or locking issue
}
```

`TokenRefreshException::getResponseData()` returns the parsed OAuth error response as an array, or `null` if no structured response was available.
