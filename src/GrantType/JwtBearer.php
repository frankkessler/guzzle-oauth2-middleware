<?php

namespace Frankkessler\Guzzle\Oauth2\GrantType;

use GuzzleHttp\ClientInterface;
use Guzzle\Common\Exception\InvalidArgumentException;
use JWT;
use SplFileObject;

/**
 * JSON Web Token (JWT) Bearer Token Profiles for OAuth 2.0.
 *
 * @link http://tools.ietf.org/html/draft-jones-oauth-jwt-bearer-04
 */
class JwtBearer extends GrantTypeBase
{
    public $grantType = 'urn:ietf:params:oauth:grant-type:jwt-bearer';

    /**
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);

        if (!($this->getConfig('private_key') instanceof SplFileObject)) {
            throw new InvalidArgumentException('private_key needs to be instance of SplFileObject');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getRequired()
    {
        return array_merge(parent::getRequired(), ['private_key']);
    }

    /**
     * {@inheritdoc}
     */
    public function getAdditionalOptions()
    {
        return [
            'form_params' => [
                'assertion' => $this->computeJwt(),
            ],
        ];
    }

    /**
     * Compute JWT, signing with provided private key.
     */
    protected function computeJwt()
    {
        $payload = [
            'iss' => $this->getConfig('client_id'),
            'aud' => sprintf('%s/%s', rtrim($this->getConfig('base_uri'), '/'), ltrim($this->getConfig('token_url'), '/')),
            'exp' => time() + 60 * 60,
            'iat' => time(),
        ];

        return JWT::encode($payload, $this->readPrivateKey($this->getConfig('private_key')), 'RS256');
    }

    /**
     * Read private key.
     *
     * @param SplFileObject $privateKey
     *
     * @return string
     */
    protected function readPrivateKey(SplFileObject $privateKey)
    {
        $key = '';
        while (!$privateKey->eof()) {
            $key .= $privateKey->fgets();
        }

        return $key;
    }
}
