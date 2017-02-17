<?php

namespace Frankkessler\Guzzle\Oauth2\Tests;

use Frankkessler\Guzzle\Oauth2\AccessToken;
use Frankkessler\Guzzle\Oauth2\GrantType\ClientCredentials;
use Frankkessler\Guzzle\Oauth2\GrantType\RefreshToken;
use Frankkessler\Guzzle\Oauth2\Oauth2Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class OAuth2ClientTest extends \Mockery\Adapter\Phpunit\MockeryTestCase
{
    public function testClientUsesRefreshTokenOnClientRequest()
    {
        GuzzleServer::flush();
        GuzzleServer::start();

        $token_url = GuzzleServer::$url.'oauth2/token';

        GuzzleServer::enqueue([
            new Response(401, [], MockResponses::returnInvalidGrant()),
            new Response(200, [], MockResponses::returnRefreshTokenResponse()),
            new Response(200, [], MockResponses::mockApiCall()),
        ]);

        $client = new Oauth2Client([
            'auth'     => 'oauth2',
            'base_uri' => GuzzleServer::$url,
        ]);
        $credentials = [
            'client_id'     => 'test',
            'client_secret' => 'testSecret',
            'token_url'     => $token_url,
        ];

        $accessTokenGrantType = new ClientCredentials($credentials);
        $client->setGrantType($accessTokenGrantType);

        $client->setAccessToken(new AccessToken('tokenInvalid', 'client_credentials', [
            'refresh_token' => 'testRefreshToken',
        ]));

        $refresh_token = 'testRefreshToken';

        $refresh_token_config = array_replace($credentials, [
            'refresh_token' => $refresh_token,
            'token_url'     => $token_url,
            'auth_location' => 'body',
        ]);
        $client->setRefreshTokenGrantType(new RefreshToken($refresh_token_config));

        $client->setRefreshToken($refresh_token);

        $response = $client->get('api/collection');
        /* @var Response $response */
        $this->assertEquals(MockResponses::mockApiCall(), (string) $response->getBody());
        $this->assertEquals(200, (string) $response->getStatusCode());

        // Now, the access token should be valid.
        $this->assertFalse($client->getAccessToken()->isExpired());

        $i = 1;
        foreach (GuzzleServer::received() as $request) {
            /* @var Request $request */
            if ($i == 2) {
                $this->assertEquals('client_secret=testSecret&scope=&refresh_token=testRefreshToken&client_id=test&grant_type=refresh_token', (string) $request->getBody());
            }
            $i++;
        }

        GuzzleServer::flush();
    }

    public function testClientUsesRefreshTokenOnExpiredAccessToken()
    {
        GuzzleServer::flush();
        GuzzleServer::start();

        GuzzleServer::enqueue([
            new Response(200, [], MockResponses::returnRefreshTokenResponse()),
            new Response(200, [], MockResponses::mockApiCall()),
        ]);

        $client = new Oauth2Client([
            'auth'     => 'oauth2',
            'base_uri' => GuzzleServer::$url,
        ]);
        $credentials = [
            'client_id'     => 'test',
            'client_secret' => 'testSecret',
            'token_url'     => GuzzleServer::$url.'oauth2/token',
        ];

        $accessTokenGrantType = new ClientCredentials($credentials);
        $client->setGrantType($accessTokenGrantType);

        $client->setAccessToken(new AccessToken('tokenInvalid', 'client_credentials', [
            'refresh_token' => 'testRefreshToken',
            'expires'       => time() - 500,
        ]));

        $refresh_token = 'testRefreshToken';

        $refresh_token_config = array_replace($credentials, [
            'refresh_token' => $refresh_token,
            'token_url'     => GuzzleServer::$url.'oauth2/token',
            'auth_location' => 'body',
        ]);
        $client->setRefreshTokenGrantType(new RefreshToken($refresh_token_config));

        $client->setRefreshToken($refresh_token);

        $response = $client->get('api/collection');
        /* @var Response $response */
        $this->assertEquals(MockResponses::mockApiCall(), (string) $response->getBody());
        $this->assertEquals(200, (string) $response->getStatusCode());

        // Now, the access token should be valid.
        $this->assertFalse($client->getAccessToken()->isExpired());

        $i = 1;
        foreach (GuzzleServer::received() as $request) {
            /* @var Request $request */
            if ($i == 1) {
                $this->assertEquals('client_secret=testSecret&scope=&refresh_token=testRefreshToken&client_id=test&grant_type=refresh_token', (string) $request->getBody());
            }
            $i++;
        }

        GuzzleServer::flush();
    }

    public function testSettingManualAccessToken()
    {
        $client = new Oauth2Client([
            'auth'        => 'oauth2',
        ]);

        // Set a valid token.
        $client->setAccessToken('testToken');
        $this->assertEquals($client->getAccessToken()->getToken(), 'testToken');
        $this->assertFalse($client->getAccessToken()->isExpired());
    }

    public function testSettingManualRefreshToken()
    {
        $client = new Oauth2Client();
        $client->setRefreshToken('testRefreshToken');
        $this->assertEquals('refresh_token', $client->getRefreshToken()->getType());
        $this->assertEquals('testRefreshToken', $client->getRefreshToken()->getToken());
    }
}
