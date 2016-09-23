<?php

namespace Frankkessler\Guzzle\Oauth2\Tests;

class MockResponses
{
    /**
     * @return string
     */
    public static function mockApiCall()
    {
        return json_encode('Hello World!');
    }

    public static function returnInvalidGrant()
    {
        return '[{"message":"Authentication failure","errorCode":"invalid_grant"}]';
    }

    public static function returnAuthorizationCodeAccessTokenResponse()
    {
        return
            '{
            "access_token": "AUTH_TEST_TOKEN",
            "refresh_token": "AUTH_TEST_REFRESH_TOKEN",
            "instance_url": "https://api.test.com",
            "token_type": "bearer"
        }';
    }

    public static function returnRefreshTokenResponse()
    {
        return
            '{
            "id":"https://login.test.com/id/00Dx0000000BV7z/005x00000012Q9P",
            "issued_at":"1278448384422",
            "expires_in": 3600,
            "instance_url":"https://api.test.com/",
            "signature":"SSSbLO/gBhmmyNUvN18ODBDFYHzakxOMgqYtu+hDPsc=",
            "access_token":"00Dx0000000BV7z!AR8AQP0jITN80ESEsj5EbaZTFG0RNBaT1cyWk7TrqoDjoNIWQ2ME_sTZzBjfmOE6zMHq6y8PIW4eWze9JksNEkWUl.Cju7m4"
        }';
    }
}
