<?php

namespace Frankkessler\Guzzle\Oauth2;

class Utilities
{
    public static function getAuthorizationUrl($authorization_url_base, $config)
    {
        $default_config = [
            'response_type' => 'code',
            'access_type'   => 'offline',
            'client_id'     => '',
            'redirect_uri'  => '',
            'scope'         => [],
        ];
        $config = array_merge($default_config, $config);

        $config['scope'] = (is_array($config['scope'])) ? implode(' ', $config['scope']) : $config['scope'];

        //$config['redirect_uri'] = urlencode($config['redirect_uri']);

        return $authorization_url_base.'?'.http_build_query($config);
    }
}
