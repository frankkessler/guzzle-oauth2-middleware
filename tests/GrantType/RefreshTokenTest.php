<?php

namespace Frankkessler\Guzzle\Oauth2\Tests\GrantType;

use Frankkessler\Guzzle\Oauth2\GrantType\RefreshToken;
use Frankkessler\Guzzle\Oauth2\Oauth2Client;

class RefreshTokenTest extends \Mockery\Adapter\Phpunit\MockeryTestCase
{
    public function testGetTokenChecksForRefreshToken()
    {
        $client = new Oauth2Client([
            'auth' => 'oauth2',
        ]);

        $grantType = new RefreshToken(['client_id' => 'test']);
        $client->setGrantType($grantType);

        $this->setExpectedException('\\RuntimeException');
        $client->getAccessToken();
    }

    public function testSetRefreshToken()
    {
        $refreshToken = 'testRefreshToken';
        $grantType = new RefreshToken(['client_id' => 'test']);
        $grantType->setRefreshToken($refreshToken);

        $this->assertEquals($refreshToken, $grantType->getConfig('refresh_token'));
    }
}
