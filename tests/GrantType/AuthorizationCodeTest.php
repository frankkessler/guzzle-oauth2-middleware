<?php

namespace Frankkessler\Guzzle\Oauth2\Tests\GrantType;

use Frankkessler\Guzzle\Oauth2\GrantType\AuthorizationCode;

class AuthorizationCodeTest extends \Mockery\Adapter\Phpunit\MockeryTestCase
{
    public function testMissingConfigException()
    {
        $this->setExpectedException('\\InvalidArgumentException', 'Config is missing the following keys: client_id, code');
        new AuthorizationCode([]);
    }
}
