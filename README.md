guzzle-oauth2-middleware
====================

Adapted from Commerceguys/guzzle-oauth2-plugin
Provides an OAuth2 middleware for [Guzzle](http://guzzlephp.org/) version 6+.

[![Build Status](https://travis-ci.org/frankkessler/guzzle-oauth2-middleware.svg)](https://travis-ci.org/frankkessler/guzzle-oauth2-middleware)
[![Coverage Status](https://coveralls.io/repos/github/frankkessler/guzzle-oauth2-middleware/badge.svg?branch=master)](https://coveralls.io/github/frankkessler/guzzle-oauth2-middleware?branch=master)
[![StyleCI](https://styleci.io/repos/68926626/shield)](https://styleci.io/repos/68926626)
[![Latest Stable Version](https://poser.pugx.org/frankkessler/guzzle-oauth2-middleware/v/stable)](https://packagist.org/packages/frankkessler/guzzle-oauth2-middleware)


## Features

- Acquires access tokens via one of the supported grant types (code, client credentials,
  user credentials, refresh token). Or you can set an access token yourself.
- Supports refresh tokens (stores them and uses them to get new access tokens).
- Handles token expiration (acquires new tokens and retries failed requests).

## Running the tests

On Windows, you must put the openssl.cnf file that comes with your version of PHP at the following location:

```
C:\usr\local\ssl\openssl.cnf
```

First make sure you have all the dependencies in place by running `composer install --prefer-dist`.  You'll also need node installed to run the tests.  You can simply run `make test` to start the node server, run the tests and then shut down the node server.

Alternatively, if you would like to run the node server in debug mode, you can run `node tests/server.js 8126 true` and then run `vendor/bin/phpunit` to run the tests.

## Example
```php
use GuzzleHttp\Client;
use Frankkessler\Guzzle\Oauth2\GrantType\RefreshToken;
use Frankkessler\Guzzle\Oauth2\GrantType\PasswordCredentials;
use Frankkessler\Guzzle\Oauth2\Oauth2Client;

$base_uri = 'https://example.com';

$client = new Oauth2Client(['base_uri' => $base_uri]);

$config = [
    'username' => 'test@example.com',
    'password' => 'test password',
    'client_id' => 'test-client',
    'scope' => 'administration',
];

$token = new PasswordCredentials($config);
$client->setGrantType($token);

$refreshToken = new RefreshToken($config);
$client->setRefreshTokenGrantType($refreshToken);

$response = $client->get('https://example.com/api/user/me');

$response_headers = $response->getHeaders();

$response_code = $response->getStatusCode();

$response_body = (string) $response->getBody();

// Use $client->getAccessToken(); and $client->getRefreshToken() to get tokens
// that can be persisted for subsequent requests.

```
