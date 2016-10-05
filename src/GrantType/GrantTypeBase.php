<?php

namespace Frankkessler\Guzzle\Oauth2\GrantType;

use Guzzle\Common\Exception\InvalidArgumentException;

abstract class GrantTypeBase implements GrantTypeInterface
{
    /** @var array Configuration settings */
    public $config = [];

    /** @var string */
    public $grantType = '';

    /**
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->getDefaults(), $config);

        if ($diff = array_diff($this->getRequired(), array_keys($this->config))) {
            throw new InvalidArgumentException('Config is missing the following keys: '.implode(', ', $diff));
        }
    }

    /**
     * @param null $key
     *
     * @return string|array|null
     */
    public function getConfig($key = null)
    {
        if ($key) {
            return isset($this->config[$key]) ? $this->config[$key] : null;
        } else {
            return $this->config;
        }
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @return void
     */
    public function setConfig($key, $value)
    {
        $this->config[$key] = $value;
    }

    /**
     * Get default configuration items.
     *
     * @return array
     */
    protected function getDefaults()
    {
        return [
            'client_secret'              => '',
            'scope'                      => '',
            'token_url'                  => 'oauth2/token',
            'auth_location'              => 'headers',
            'body_type'                  => 'form_params',
            'base_uri'                   => null,
            'jwt_private_key_passphrase' => null,
            'jwt_payload'                => [],
            'jwt_algorithm'              => 'RS256',
        ];
    }

    /**
     * Get required configuration items.
     *
     * @return string[]
     */
    protected function getRequired()
    {
        return ['client_id'];
    }

    /**
     * Get additional options, if any.
     *
     * @return array|null
     */
    public function getAdditionalOptions()
    {
        return [];
    }
}
