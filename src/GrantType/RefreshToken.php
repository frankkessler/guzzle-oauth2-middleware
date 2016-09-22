<?php

namespace Frankkessler\Guzzle\Oauth2\GrantType;

/**
 * Refresh token grant type.
 *
 * @link http://tools.ietf.org/html/rfc6749#section-6
 */
class RefreshToken extends GrantTypeBase implements RefreshTokenGrantTypeInterface
{
    public $grantType = 'refresh_token';

    /**
     * {@inheritdoc}
     */
    protected function getDefaults()
    {
        return parent::getDefaults() + ['refresh_token' => ''];
    }

    /**
     * {@inheritdoc}
     */
    public function setRefreshToken($refreshToken)
    {
        $this->config['refresh_token'] = $refreshToken;
    }

    /**
     * {@inheritdoc}
     */
    public function hasRefreshToken()
    {
        return !empty($this->config['refresh_token']);
    }
}
