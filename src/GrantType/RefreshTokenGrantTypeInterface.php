<?php

namespace Frankkessler\Guzzle\Oauth2\GrantType;

interface RefreshTokenGrantTypeInterface
{
    /**
     * @param string $refreshToken
     */
    public function setRefreshToken($refreshToken);

    /**
     * @return bool
     */
    public function hasRefreshToken();
}
