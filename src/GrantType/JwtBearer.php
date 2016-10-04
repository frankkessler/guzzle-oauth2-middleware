<?php

namespace Frankkessler\Guzzle\Oauth2\GrantType;

use Guzzle\Common\Exception\InvalidArgumentException;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Key;

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
            'aud' => rtrim($this->getConfig('base_uri'), '/'),
            'exp' => time() + 60 * 60,
            'iat' => time(),
            'sub' => '',
        ];

        if(isset($this->config['payload']) && is_array($this->config['payload'])){
            $payload = array_replace($payload, $this->config['payload']);
        }

        $algorithm = (isset($this->config['algorithm']))?$this->config['algorithm']:'RS256';

        $signer = $this->signerFactory($algorithm);

        $privateKey = new Key(file_get_contents(__DIR__.'/../../build/cert.key'), "testpassword");

        $token = (new Builder())->setIssuer($payload['iss'])
        ->setAudience($payload['aud'])
        ->setIssuedAt($payload['iat'])
        ->setExpiration($payload['exp'])
        ->setSubject($payload['sub'])
        ->sign($signer,  $privateKey) // creates a signature using your private key
        ->getToken(); // Retrieves the generated token

        return (string) $token;
    }

    /**
     * @param $algo
     *
     * @return Signer
     */
    protected function signerFactory($algo)
    {
        switch($algo){
            case 'RS256': return new \Lcobucci\JWT\Signer\Rsa\Sha256();
                break;
            default: return new \Lcobucci\JWT\Signer\Rsa\Sha256();
        }
    }
}
