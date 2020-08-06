<?php

namespace MauticPlugin\AutomationBundle\Security\Provider;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;

class KeycloakProvider extends \Stevenmaguire\OAuth2\Client\Provider\Keycloak
{
    /**
     * Requests and returns an access token from refresh token.
     *
     * @return AccessToken
     */
    public function getRefreshToken(AccessToken $token)
    {
        $params = [
            'refresh_token' => $token->getRefreshToken(),
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
        ];

        return $this->getAccessToken('refresh_token', $params);
    }

    /**
     * Requests and returns an authorization token from access token.
     *
     * @return AccessToken
     */
    public function getAuthorizationToken(AccessToken $token)
    {
        $params = [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:uma-ticket',
            'audience'   => $this->clientId,
        ];

        $request  = $this->getAuthorizationTokenRequest($token, $params);

        return $this->getParsedResponse($request);
    }

    /**
     * Returns a prepared request for requesting an authorization token.
     *
     * @param $token
     * @param array $params Query string parameters
     *
     * @return \Psr\Http\Message\RequestInterface
     */
    protected function getAuthorizationTokenRequest($token, array $params)
    {
        $method  = $this->getAccessTokenMethod();
        $url     = $this->getAccessTokenUrl($params);
        $options = $this->getAccessTokenOptions($params);

        return $this->getAuthenticatedRequest($method, $url, $token, $options);
    }

    /**
     * Check a provider response for errors.
     *
     * @throws IdentityProviderException
     *
     * @param string $data Parsed response data
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        if (!empty($data['error'])) {
            $error = $data['error'].': '.$data['error_description'];
            throw new IdentityProviderException($error, $response->getStatusCode(), $data);
        }
    }

    /**
     * Returns the authorization headers used by this provider.
     *
     * Typically this is "Bearer" or "MAC". For more information see:
     * http://tools.ietf.org/html/rfc6749#section-7.1
     *
     * No default is provided, providers must overload this method to activate
     * authorization headers.
     *
     * @param mixed|null $token Either a string or an access token instance
     *
     * @return array
     */
    protected function getAuthorizationHeaders($token = null)
    {
        return ['Authorization' => 'Bearer '.$token->getToken()];
    }
}
