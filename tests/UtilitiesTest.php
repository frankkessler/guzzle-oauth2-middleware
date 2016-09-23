<?php

namespace Frankkessler\Guzzle\Oauth2\Tests;

use Frankkessler\Guzzle\Oauth2\AccessToken;
use Frankkessler\Guzzle\Oauth2\GrantType\ClientCredentials;
use Frankkessler\Guzzle\Oauth2\GrantType\RefreshToken;
use Frankkessler\Guzzle\Oauth2\Oauth2Client;
use Frankkessler\Guzzle\Oauth2\Tests\GuzzleServer;
use Frankkessler\Guzzle\Oauth2\Tests\MockResponses;
use Frankkessler\Guzzle\Oauth2\Utilities;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class UtilitiesTest extends \Mockery\Adapter\Phpunit\MockeryTestCase
{
   public function testClientUsesRefreshTokenOnClientRequest()
    {
        $callback_url = 'https://www.test.com/oauth/callback';

        $authorization_url = Utilities::getAuthorizationUrl('https://www.test.com',[
            'client_id'     => 'test_client_id',
            'scope'         => '',
            'redirect_uri'  => $callback_url,
        ]);

        $this->assertEquals('https://www.test.com?response_type=code&access_type=offline&client_id=test_client_id&redirect_uri='.urlencode($callback_url).'&scope=', $authorization_url);
    }
}
