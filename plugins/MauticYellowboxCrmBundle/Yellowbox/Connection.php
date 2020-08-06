<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticYellowboxCrmBundle\Yellowbox;

use GuzzleHttp\Psr7\Response;
use Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException;
use Mautic\IntegrationsBundle\Sync\Logger\DebugLogger;
use MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException;
use MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException;
use MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidQueryArgumentException;
use MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException;
use MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException;
use MauticPlugin\MauticYellowboxCrmBundle\Exceptions\YellowboxPluginException;
use MauticPlugin\MauticYellowboxCrmBundle\Integration\Provider\YellowboxSettingProvider;
use MauticPlugin\MauticYellowboxCrmBundle\Integration\YellowboxCrmIntegration;
use MauticPlugin\MauticYellowboxCrmBundle\Model\Credentials;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Connection.
 */
class Connection
{
    /**
     * @var string
     */
    private $apiDomain;

    /**
     * @var array
     */
    private $requestHeaders = [
        //'Content-type' => 'application/json;charset=UTF-8',
    ];

    /**
     * @var \GuzzleHttp\Client
     */
    private $httpClient;

    /**
     * @var string
     */
    private $bearer;

    /**
     * @var Credentials
     */
    private $credentials;

    /**
     * @var YellowboxSettingProvider
     */
    private $settings;

    public function __construct(\GuzzleHttp\Client $client, YellowboxSettingProvider $settings)
    {
        $this->settings   = $settings;
        $this->httpClient = $client;
    }

    /**
     * @return mixed|ResponseInterface
     *
     * @throws AccessDeniedException
     * @throws DatabaseQueryException
     * @throws InvalidQueryArgumentException
     * @throws InvalidRequestException
     * @throws PluginNotConfiguredException
     * @throws SessionException
     * @throws YellowboxPluginException
     */
    public function get(string $operation, array $payload = [], array $json = [])
    {
        $this->settings->exceptConfigured();

        if (!$this->isAuthenticated()) {
            $this->authenticate();
        }
        $this->requestHeaders['Authorization'] = 'Bearer '.$this->bearer;

        $query = sprintf(
            '%s/%s',
            $this->getApiUrl(),
            $operation
        );

        if (count($payload)) {
            $query .= '?'.http_build_query($payload);
        }

        DebugLogger::log(YellowboxCrmIntegration::NAME, 'Running Yellowbox query: '.$query);
        $response = $this->httpClient->get(
            $query,
            [
                'headers' => $this->requestHeaders,
            ]
        );

        return $this->handleResponse($response, $query);
    }

    /**
     * @return mixed
     *
     * @throws AccessDeniedException
     * @throws DatabaseQueryException
     * @throws InvalidQueryArgumentException
     * @throws InvalidRequestException
     * @throws SessionException
     * @throws YellowboxPluginException
     * @throws \Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException
     */
    public function post(string $operation, array $payload)
    {
        $this->settings->exceptConfigured();

        if (!$this->isAuthenticated()) {
            $this->authenticate();
        }

        $this->requestHeaders['Authorization'] = 'Bearer '.$this->bearer;

        $query = sprintf(
            '%s/%s',
            $this->getApiUrl(),
            $operation
        );
        $response = $this->httpClient->post($query,
            [
                'headers' => $this->requestHeaders,
                'json'    => $payload,
            ]);

        return $this->handleResponse($response);
    }

    /**
     * @return mixed
     *
     * @throws AccessDeniedException
     * @throws DatabaseQueryException
     * @throws InvalidQueryArgumentException
     * @throws InvalidRequestException
     * @throws SessionException
     * @throws YellowboxPluginException
     */
    private function handleResponse(Response $response)
    {
        $content = $response->getBody()->getContents();
        if (200 !== $response->getStatusCode()) {
            throw new YellowboxPluginException(sprintf('Server responded with an error %s', $response->getStatusCode()));
        }
        if (!$result = json_decode($content)) {
            $result = $content;
        }

        if (false === $result || null === $result) {
            throw new YellowboxPluginException('Incorrect endpoint response');
        }

        return $result;
    }

    /**
     * Test connection settings.
     * Proxy to authenticate method.
     * Is possible, that given arguments are null.
     */
    public function test(string $url = null, string $username = null, string $password = null): bool
    {
        try {
            $this->authenticate($url, $username, $password);
        } catch (PluginNotConfiguredException $e) {
            return false;
        }

        return true;
    }

    /**
     * @throws PluginNotConfiguredException
     */
    private function authenticate(string $url = null, string $username = null, string $password = null): Connection
    {
        $this->setCredentials($url, $username, $password);

        try {
            $credentials = $this->credentials;
            if (is_null($credentials)) {
                throw new SessionException('No authentication credentials supplied');
            }

            $this->requestHeaders['Authorization'] = 'Basic '.$credentials->getEncodedValue();
            $query                                 = sprintf('%s/authentication', $this->getApiUrl());
            $response                              = $this->httpClient->get(
                $query,
                [
                    'headers' => $this->requestHeaders,
                ]
            );
            $result                                = $this->handleResponse($response);
            $this->bearer                          = $result;
        } catch (\Exception $e) {
            throw new PluginNotConfiguredException($e->getMessage());
        }

        return $this;
    }

    /**
     * @throws PluginNotConfiguredException
     */
    private function getApiDomain(): string
    {
        if (!$this->apiDomain) {
            throw new PluginNotConfiguredException('No authentication credentials supplied');
        }

        return $this->apiDomain;
    }

    /**
     * @throws PluginNotConfiguredException
     */
    private function getApiUrl(): string
    {
        return sprintf('%s/ws', $this->getApiDomain());
    }

    private function isAuthenticated(): bool
    {
        return !is_null($this->bearer);
    }

    private function setCredentials(string $url = null, string $username = null, string $password = null): void
    {
        if ($url && $username && $password) {
            $credentialsCfg = [
                'url'      => $url,
                'username' => $username,
                'password' => $password,
            ];
        } else {
            $credentialsCfg = $this->settings->getCredentials();
        }

        if (!empty($credentialsCfg['password']) && !empty($credentialsCfg['username']) && !empty($credentialsCfg['url'])) {
            $this->credentials = new Credentials($credentialsCfg['password'], $credentialsCfg['username']);

            $this->apiDomain = $credentialsCfg['url'];
        }
    }
}
