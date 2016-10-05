<?php

namespace Frankkessler\Guzzle\Oauth2;

use Exception;
use Frankkessler\Guzzle\Oauth2\Exceptions\InvalidGrantException;
use Frankkessler\Guzzle\Oauth2\GrantType\GrantTypeBase;
use Frankkessler\Guzzle\Oauth2\GrantType\GrantTypeInterface;
use Frankkessler\Guzzle\Oauth2\GrantType\RefreshTokenGrantTypeInterface;
use Frankkessler\Guzzle\Oauth2\Middleware\RetryModifyRequestMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Oauth2Client extends Client
{
    /** @var AccessToken|null */
    protected $accessToken;
    /** @var AccessToken|null */
    protected $refreshToken;

    /** @var GrantTypeInterface */
    protected $grantType;
    /** @var RefreshTokenGrantTypeInterface */
    protected $refreshTokenGrantType;

    protected $config;

    public function __construct($config = [])
    {
        $this->config = $config;

        //allow a different handler stack to completely override the default stack for oauth
        if (!isset($config['handler'])) {
            $config['handler'] = $this->returnHandlers();
        }

        parent::__construct($config);
    }

    /**
     * Set the middleware handlers for all requests using Oauth2.
     *
     * @return HandlerStack|null
     */
    protected function returnHandlers()
    {
        // Create a handler stack that has all of the default middlewares attached
        $handler = HandlerStack::create();

        //Add the Authorization header to requests.
        $handler->push(Middleware::mapRequest(function (RequestInterface $request) {
            if ($this->getConfig('auth') == 'oauth2') {
                $token = $this->getAccessToken();

                if ($token !== null) {
                    $request = $request->withHeader('Authorization', 'Bearer '.$token->getToken());

                    return $request;
                }
            }

            return $request;
        }), 'add_oauth_header');

        $handler->before('add_oauth_header', $this->retry_modify_request(function ($retries, RequestInterface $request, ResponseInterface $response = null, $error = null) {
            if ($retries > 0) {
                return false;
            }
            if ($response instanceof ResponseInterface) {
                if (in_array($response->getStatusCode(), [400, 401])) {
                    return true;
                }
            }

            return false;
        },
            function (RequestInterface $request, ResponseInterface $response) {
                if ($response instanceof ResponseInterface) {
                    if (in_array($response->getStatusCode(), [400, 401])) {
                        $token = $this->acquireAccessToken();
                        $this->setAccessToken($token, 'Bearer');

                        $modify['set_headers']['Authorization'] = 'Bearer '.$token->getToken();

                        return Psr7\modify_request($request, $modify);
                    }
                }

                return $request;
            }
        ));

        return $handler;
    }

    /**
     * Retry Call after updating access token.
     */
    public function retry_modify_request(callable $decider, callable $requestModifier, callable $delay = null)
    {
        return function (callable $handler) use ($decider, $requestModifier, $delay) {
            return new RetryModifyRequestMiddleware($decider, $requestModifier, $handler, $delay);
        };
    }

    /**
     * Get a new access token.
     *
     * @return AccessToken|null
     */
    protected function acquireAccessToken()
    {
        $accessToken = null;

        if ($this->refreshTokenGrantType) {
            if ($this->refreshTokenGrantType->hasRefreshToken()) {
                $accessToken = $this->getToken($this->refreshTokenGrantType);
            }
        }

        if (!$accessToken && $this->grantType) {
            // Get a new access token.
            $accessToken = $this->getToken($this->grantType);
        }

        return $accessToken ?: null;
    }

    /**
     * Get the access token.
     *
     * @return AccessToken|null Oauth2 access token
     */
    public function getAccessToken()
    {
        if ($this->accessToken && $this->accessToken->isExpired()) {
            // The access token has expired.
            $this->accessToken = null;
        }

        if (null === $this->accessToken) {
            // Try to acquire a new access token from the server.
            $this->accessToken = $this->acquireAccessToken();
            if ($this->accessToken) {
                $this->refreshToken = $this->accessToken->getRefreshToken() ?: null;
            }
        }

        return $this->accessToken;
    }

    /**
     * Get the refresh token.
     *
     * @return AccessToken|null
     */
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    /**
     * Set the access token.
     *
     * @param AccessToken|string $accessToken
     * @param string             $type
     * @param int                $expires
     */
    public function setAccessToken($accessToken, $type = null, $expires = null)
    {
        if (is_string($accessToken)) {
            $accessToken = new AccessToken($accessToken, $type, ['expires' => $expires]);
        } elseif (!$accessToken instanceof AccessToken) {
            throw new \InvalidArgumentException('Invalid access token');
        }
        $this->accessToken = $accessToken;
    }

    /**
     * Set the refresh token.
     *
     * @param AccessToken|string $refreshToken The refresh token
     */
    public function setRefreshToken($refreshToken)
    {
        if (is_string($refreshToken)) {
            $refreshToken = new AccessToken($refreshToken, 'refresh_token');
        } elseif (!$refreshToken instanceof AccessToken) {
            throw new \InvalidArgumentException('Invalid refresh token');
        }
        $this->refreshToken = $refreshToken;
    }

    public function getToken(GrantTypeBase $grantType)
    {
        $token_client_config = [];

        if (isset($this->config['token_handler'])) {
            $token_client_config['handler'] = $this->config['token_handler'];
        }

        $client = new Client($token_client_config);
        $config = $grantType->getConfig();

        $form_params = $config;
        $form_params['grant_type'] = $grantType->grantType;
        unset($form_params['token_url'], $form_params['auth_location'], $form_params['body_type'], $form_params['base_uri'], $form_params['jwt_private_key'], $form_params['jwt_private_key_passphrase'], $form_params['jwt_payload'], $form_params['jwt_algorithm']);

        $requestOptions = [];

        if ($config['auth_location'] !== 'body') {
            $requestOptions['auth'] = [$config['client_id'], $config['client_secret']];
            unset($form_params['client_id'], $form_params['client_secret']);
        }

        if ($config['body_type'] == 'json') {
            $requestOptions['json'] = $form_params;
        } else {
            $requestOptions['form_params'] = $form_params;
        }

        if ($additionalOptions = $grantType->getAdditionalOptions()) {
            $requestOptions = array_merge_recursive($requestOptions, $additionalOptions);
        }
        $requestOptions['http_errors'] = false;

        $response = $client->post($config['token_url'], $requestOptions);
        /** @var Psr7\Response $data */
        $data = json_decode((string) $response->getBody(), true);

        if (isset($data['access_token'])) {
            return new AccessToken($data['access_token'], isset($data['token_type']) ? $data['token_type'] : '', $data);
        } elseif (isset($data['error'])) {
            switch ($data['error']) {
                case 'invalid_grant': throw(new InvalidGrantException('invalid_grant', (isset($data['status_code'])) ? $data['status_code'] : 0));
                    break;
                default:
                    throw(new Exception($data['error'], (isset($data['status_code'])) ? $data['status_code'] : 0));
                    break;
            }
        } elseif ($response->getStatusCode() == 401) {
            throw(new InvalidGrantException('invalid_grant', (isset($data['status_code'])) ? $data['status_code'] : 0));
        }
    }

    public function setGrantType(GrantTypeBase $grantType)
    {
        if (isset($this->config['base_uri'])) {
            $grantType->setConfig('base_uri', $this->config['base_uri']);
        }

        $this->grantType = $grantType;
    }

    public function setRefreshTokenGrantType(RefreshTokenGrantTypeInterface $refreshTokenGrantType)
    {
        $this->refreshTokenGrantType = $refreshTokenGrantType;
    }
}
