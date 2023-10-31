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

## Testing

``` bash
$ ./vendor/bin/phpunit
```

## With PHP >=7.3

```
docker-compose build
docker-compose up -d
docker run -it oauth2-geocaching-php7
```
## License

The MIT License (MIT). Please see [License File](https://github.com/Surfoo/oauth2-geocaching/blob/master/LICENSE) for more information.
