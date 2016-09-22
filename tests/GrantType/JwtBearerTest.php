<?php

namespace Frankkessler\Guzzle\Oauth2\Tests\GrantType;

use Frankkessler\Guzzle\Oauth2\GrantType\JwtBearer;
use Frankkessler\Guzzle\Oauth2\Oauth2Client;
use Frankkessler\Guzzle\Oauth2\Tests\GuzzleServer;
use Frankkessler\Guzzle\Oauth2\Tests\MockResponses;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use SplFileObject;

class JwtBearerTest extends \Mockery\Adapter\Phpunit\MockeryTestCase
{
    public function testMissingConfigException()
    {
        $this->setExpectedException('\\InvalidArgumentException', 'Config is missing the following keys: client_id, private_key');
        new JwtBearer([]);
    }

    public function testPrivateKeyNotSplFileObject()
    {
        $this->setExpectedException('\\InvalidArgumentException', 'private_key needs to be instance of SplFileObject');
        $grantType = new JwtBearer([
            'client_id'   => 'testClient',
            'private_key' => 'INVALID',
        ]);
    }

    public function testValidRequestGetsToken()
    {
        GuzzleServer::flush();
        GuzzleServer::start();

        $token_url = GuzzleServer::$url.'oauth2/token';

        GuzzleServer::enqueue([
            new Response(200, [], MockResponses::returnRefreshTokenResponse()),
        ]);

        $client = new Oauth2Client([
            'auth' => 'oauth2',
            'base_uri' => GuzzleServer::$url,
        ]);

        $grantType = new JwtBearer([
            'client_id'   => 'testClient',
            'private_key' => new SplFileObject(__DIR__.'/../private.key'),
            'token_url'   => $token_url,
        ]);

        $client->setGrantType($grantType);

        $token = $client->getAccessToken();
        $this->assertNotEmpty($token->getToken());
        $this->assertTrue($token->getExpires()->getTimestamp() > time());

        foreach (GuzzleServer::received() as $request) {
            /** @var Request $request */
                $this->assertContains("scope=&grant_type=urn%3Aietf%3Aparams%3Aoauth%3Agrant-type%3Ajwt-bearer&assertion=", (string)$request->getBody());
        }

        GuzzleServer::flush();
    }
}
