<?php

namespace Frankkessler\Guzzle\Oauth2\Tests\GrantType;

use Frankkessler\Guzzle\Oauth2\AccessToken;
use Frankkessler\Guzzle\Oauth2\GrantType\PasswordCredentials;
use Frankkessler\Guzzle\Oauth2\Oauth2Client;
use Frankkessler\Guzzle\Oauth2\Tests\GuzzleServer;
use Frankkessler\Guzzle\Oauth2\Tests\MockResponses;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class PasswordCredentialsTest extends \Mockery\Adapter\Phpunit\MockeryTestCase
{
    public function testMissingConfigException()
    {
        $this->setExpectedException('\\InvalidArgumentException', 'Config is missing the following keys: client_id, username, password');
        new PasswordCredentials();
    }

    public function testValidPasswordGetsToken()
    {
        GuzzleServer::flush();
        GuzzleServer::start();

        $token_url = GuzzleServer::$url.'oauth2/token';

        GuzzleServer::enqueue([
            new Response(200, [], MockResponses::returnRefreshTokenResponse()),
        ]);

        $client = new Oauth2Client([
            'auth'     => 'oauth2',
            'base_uri' => GuzzleServer::$url,
        ]);

        $grantType = new PasswordCredentials([
            'client_id' => 'testClient',
            'username'  => 'validUsername',
            'password'  => 'validPassword',
            'token_url' => $token_url,
        ]);

        $client->setGrantType($grantType);

        $token = $client->getAccessToken();
        $this->assertNotEmpty($token->getToken());
        $this->assertTrue($token->getExpires()->getTimestamp() > time());

        foreach (GuzzleServer::received() as $request) {
            /* @var Request $request */
            $this->assertEquals('scope=&username=validUsername&password=validPassword&grant_type=password', (string) $request->getBody());
        }

        GuzzleServer::flush();
    }

    public function testInvalidGrantPasswordGetsToken()
    {
        GuzzleServer::flush();
        GuzzleServer::start();

        $token_url = GuzzleServer::$url.'oauth2/token';

        GuzzleServer::enqueue([
            new Response(401, [], MockResponses::returnInvalidGrant()),
        ]);

        $client = new Oauth2Client([
            'auth'     => 'oauth2',
            'base_uri' => GuzzleServer::$url,
        ]);

        $grantType = new PasswordCredentials([
            'client_id' => 'testClient',
            'username'  => 'validUsername',
            'password'  => 'validPassword',
            'token_url' => $token_url,
        ]);

        $client->setGrantType($grantType);

        $client->setAccessToken(new AccessToken('bad_token', 'Bearer', [
            'expires' => time() - 500,
        ]));

        $this->setExpectedException('\\Frankkessler\\Guzzle\\Oauth2\\Exceptions\\InvalidGrantException');
        $token = $client->getAccessToken();

        GuzzleServer::flush();
    }
}
