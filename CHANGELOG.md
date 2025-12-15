# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.0.0] - 2025-12-xx
### Added
    - Token management primitives: `TokenSet`, `TokenStorageInterface` with locking, and serialization helpers.
    - `TokenRefreshPlugin` to refresh access tokens on 401 responses and retry requests safely.
    - Token refresh exceptions (`TokenRefreshException`, `RefreshTokenExpiredException`, `TokenStorageException`) for clearer error handling.
