<?php

namespace Frankkessler\Guzzle\Oauth2\GrantType;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer;
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
        return array_merge(parent::getRequired(), ['jwt_private_key']);
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

        if (isset($this->config['jwt_payload']) && is_array($this->config['jwt_payload'])) {
            $payload = array_replace($payload, $this->config['jwt_payload']);
        }

        $signer = $this->signerFactory($this->config['jwt_algorithm']);

        $privateKey = new Key(file_get_contents($this->config['jwt_private_key']), $this->config['jwt_private_key_passphrase']);

        $builder = $this->tokenBuilderFactory($payload);

        $token = $builder->sign($signer, $privateKey)
              ->getToken();

        return (string) $token;
    }

    protected function tokenBuilderFactory($payload)
    {
        $token = new Builder();

        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $sub_value) {
                    $token->set($key, $sub_value);
                }
            } else {
                $token->set($key, $value);
            }
        }

        return $token;
    }

    /**
     * @param $algo
     *
     * @return Signer
     */
    protected function signerFactory($algo)
    {
        switch ($algo) {
            case 'RS256': return new \Lcobucci\JWT\Signer\Rsa\Sha256();
                break;
            default: return new \Lcobucci\JWT\Signer\Rsa\Sha256();
        }
    }
}
