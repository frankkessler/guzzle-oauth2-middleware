<?php

namespace Frankkessler\Guzzle\Oauth2\Tests;

use Frankkessler\Guzzle\Oauth2\Utilities;

class UtilitiesTest extends \Mockery\Adapter\Phpunit\MockeryTestCase
{
    public function testClientUsesRefreshTokenOnClientRequest()
    {
        $callback_url = 'https://www.test.com/oauth/callback';

        $authorization_url = Utilities::getAuthorizationUrl('https://www.test.com', [
            'client_id'     => 'test_client_id',
            'scope'         => '',
            'redirect_uri'  => $callback_url,
        ]);

        $this->assertEquals('https://www.test.com?response_type=code&access_type=offline&client_id=test_client_id&redirect_uri='.urlencode($callback_url).'&scope=', $authorization_url);
    }
}
