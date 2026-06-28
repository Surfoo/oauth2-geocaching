# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.0.0] - 2026-06-28
### Added
- Token management primitives: `TokenSet` (access/refresh token DTO with expiry helpers), `TokenStorageInterface` (persist tokens with locking support).
- `TokenRefreshPlugin`: HTTPlug/PSR plugin that intercepts `401` responses, refreshes the access token, and retries the original request with exponential back-off.
- Three dedicated exceptions for clearer error handling: `TokenRefreshException`, `RefreshTokenExpiredException`, `TokenStorageException`.
- `GeocachingConfig` helper to build environment-specific configuration arrays with optional URL overrides (`domain`, `apiDomain`, `oAuthDomain`).
- Custom URL overrides on the `Geocaching` provider itself — useful for Docker, corporate proxies, or internal staging infrastructure.
- `psr/log` dependency for structured logging inside `TokenRefreshPlugin`.

### Changed
- Minimum PHP version raised from `^8.1` to `>=8.2`.
- `league/oauth2-client` requirement updated from `^2.7` to `^2.9`.
- Added `php-http/client-common ^2.7` and `php-http/promise ^1.1` as required dependencies (needed for `TokenRefreshPlugin`).
- Dev tooling updated: PHPStan `^2.0`, PHPUnit `^11.0 || ^12.0`, Rector `^2.0`, PHPCS `~4.0`; dropped Mockery in favour of PHPUnit stubs.

### Removed
- Dropped support for PHP 8.1.

## [2.1.0] - 2023-10-02
### Added
- `setResourceOwnerFields(array $fields): self` — lets callers control which fields are requested from the Geocaching API `/v1/users/{referenceCode}` endpoint.

## [2.0.2] - 2023-10-01
### Removed
- `optedInFriendSharing` removed from the default resource owner fields requested by the provider.

### Changed
- Updated GitHub Actions to `actions/checkout@v3` and `codecov-action@v3`.

## [2.0.1] - 2023-08-14
### Changed
- PKCE code challenge method is now locked to `S256`; `plain` is no longer accepted.

## [2.0.0] - 2023-08-13
### Added
- PKCE support via the upstream `league/oauth2-client ^2.7` built-in PKCE flow.
- Unit test suite.

### Changed
- Minimum PHP version raised from `^7.x` to `^8.1`.
- All class constants made explicitly `public`.
- Type declarations added throughout (`string`, `array`, `?string`, return types).
- `GeocachingIdentityProviderException` marked `final`.
- Modernised codebase with Rector (PHP 8.1 idioms, null-safe operators).

### Removed
- Dropped support for PHP 7.x.
- Travis CI replaced by GitHub Actions.
- Removed `response_type` option (handled internally by the League provider base class).

## [1.4.0] - 2021-01-19
### Changed
- Library and dev-dependency upgrades; added PHP 8.0 compatibility.

## [1.3.0] - 2019-08-16
### Added
- `getJoinedDate()` and `getProfileText()` methods on `GeocachingResourceOwner` (backed by `joinedDateUtc` and `profileText` API fields).

## [1.2.0] - 2019-08-13
### Added
- `getOptedInFriendSharing()` method on `GeocachingResourceOwner`.

## [1.1.0] - 2018-11-06
### Added
- Extended `GeocachingResourceOwner` with `getAvatarUrl()`, `getBannerUrl()`, `getProfileUrl()`, `getHomeCoordinates()`, `getGeocacheLimits()`, `getMembershipLevelId()`.

## [1.0.0] - 2018-06-11
### Added
- Initial release: `Geocaching` OAuth 2.0 provider, `GeocachingResourceOwner`, and `GeocachingIdentityProviderException`.
