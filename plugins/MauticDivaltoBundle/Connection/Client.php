<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDivaltoBundle\Connection;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use kamermans\OAuth2\Exception\AccessTokenRequestException;
use Mautic\IntegrationsBundle\Auth\Provider\Oauth2TwoLegged\HttpFactory;
use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Exception\InvalidCredentialsException;
use Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException;
use MauticPlugin\MauticDivaltoBundle\Connection\Config as ConnectionConfig;
use MauticPlugin\MauticDivaltoBundle\Integration\Config;
use MauticPlugin\MauticDivaltoBundle\Integration\DivaltoIntegration;
use MauticPlugin\MauticDivaltoBundle\Sync\Mapping\Manual\MappingManualFactory;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;

class Client
{
    private $apiUrl = 'https://api.weavy.divalto.com';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ConnectionConfig
     */
    private $connectionConfig;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var string
     */
    private $bearer;

    /**
     * @var array
     */
    private $cache;

    /**
     * Client constructor.
     */
    public function __construct(HttpFactory $httpFactory, Config $config, ConnectionConfig $connectionConfig, Logger $logger)
    {
        $this->config           = $config;
        $this->connectionConfig = $connectionConfig;
        $this->logger           = $logger;
    }

    /**
     * @param array $payload
     *
     * @throws GuzzleException
     * @throws IntegrationNotFoundException
     * @throws InvalidCredentialsException
     * @throws PluginNotConfiguredException
     */
    public function get(string $objectName, ?\DateTimeInterface $startDateTime, ?\DateTimeInterface $endDateTime, int $page = 1, $payload = []): array
    {
        $client      = $this->getClient();
        $credentials = $this->getCredentials();

        $url = sprintf('%s/v1/Data/%s/Select', $this->apiUrl, $credentials->getProjectCode());

        // This imaginary API assumes support to query for created or modified items between two timestamps with native pagination

        $payload['entityName'] = $objectName;
        $payload['offset']     = 0;
        $matchedFields         = array_keys($this->config->getMappedFields($objectName));
        foreach ($matchedFields as $matchedField) {
            $payload['returnFields'][]['fieldName'] = $matchedField;
        }

        if (MappingManualFactory::CONTACT_OBJECT === $objectName) {
            $payload['returnFields'][]['fieldName'] = 'customer_ID';
        }

        if ($startDateTime) {
            $payload['filterFields'][] = [
                'filterKey'   => 'srvDateKey',
                'fieldName'   => 'srvDate',
                'operator'    => 'GreaterThan',
                'filterValues'=> [$startDateTime->format('Y-m-d H:i:s')],
            ];
        }

        if ($endDateTime) {
            $payload['filterFields'][] = [
                'filterKey'   => 'srvDateKey',
                'fieldName'   => 'srvDate',
                'operator'    => 'LessThan',
                'filterValues'=> [$endDateTime->format('Y-m-d H:i:s')],
            ];
        }

        $response = $client->request('POST', $url, ['json'=>$payload]);
        if (200 !== $response->getStatusCode()) {
            $this->logger->error(
                sprintf(
                    '%s: Error fetching %s objects: %s',
                    DivaltoIntegration::DISPLAY_NAME,
                    $objectName,
                    $response->getReasonPhrase()
                )
            );

            return [];
        }

        $this->config->log(
            sprintf('Fetch %s to Integration with filters %s', $objectName, urldecode(http_build_query($payload, '', ', ')))
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    public function find(string $objectName, $objectId)
    {
        if (!isset($this->cache[$objectName][$objectId])) {
            $client                              = $this->getClient();
            $credentials                         = $this->getCredentials();
            $url                                 = sprintf(
                '%s/v1/Data/%s/%s/%s',
                $this->apiUrl,
                $credentials->getProjectCode(),
                $objectName,
                $objectId
            );
            $response                            = $client->request('GET', $url);
            $this->cache[$objectName][$objectId] = $response;
        }

        return $this->cache[$objectName][$objectId];
    }

    public function upsert(string $method, string $objectName, array $data): ?ResponseInterface
    {
        $client      = $this->getClient();
        $credentials = $this->getCredentials();
        $url         = sprintf('%s/v1/Data/%s/%s', $this->apiUrl, $credentials->getProjectCode(), $objectName);
        if ('PUT' === $method) {
            $url = sprintf('%s/%s', $url, $data[$objectName.'_ID']);
        }
        $options      = ['json' => $data];
        $response     = $client->request($method, $url, $options);

        if (200 !== $response->getStatusCode()) {
            $this->logger->error(
                sprintf(
                    '%s: Error fetching %s objects: %s',
                    DivaltoIntegration::DISPLAY_NAME,
                    $objectName,
                    $response->getReasonPhrase()
                )
            );

            return null;
        }

        $this->config->log(
            sprintf('%s push %s to Integration with values %s', $method, $objectName, urldecode(http_build_query($data, '', ', ')))
        );

        return $response;
    }

    public function getFields(string $objectName): array
    {
        $client      = $this->getClient();
        $credentials = $this->getCredentials();
        $url         = sprintf('%s/v1/Dictionary/%s/%s', $this->apiUrl, $credentials->getProjectCode(), $objectName);

        try {
            $response = $client->request('GET', $url);
        } catch (AccessTokenRequestException $exception) {
            // Mock an access token since the authorization URL is non-existing
            die($exception);
        }

        if (200 !== $response->getStatusCode()) {
            $this->logger->error(
                sprintf(
                    '%s: Error fetching %s fields: %s',
                    DivaltoIntegration::DISPLAY_NAME,
                    $objectName,
                    $response->getReasonPhrase()
                )
            );

            return [];
        }

        $response =  json_decode($response->getBody()->getContents(), true);

        if (!isset($response['fields'])) {
            return [];
        }

        return $response['fields'];
    }

    /**
     * @throws PluginNotConfiguredException
     */
    private function getCredentials(): Credentials
    {
        if (!$this->config->isConfigured()) {
            throw new PluginNotConfiguredException();
        }
        $apiKeys = $this->config->getApiKeys();

        return new Credentials($apiKeys['username'], $apiKeys['password'], $apiKeys['projectCode']);
    }

    /**
     * @throws IntegrationNotFoundException
     */
    private function getConfig(): ConnectionConfig
    {
        $this->connectionConfig->setIntegrationConfiguration($this->config->getIntegrationEntity());

        return $this->connectionConfig;
    }

    /**
     * @throws PluginNotConfiguredException
     * @throws InvalidCredentialsException
     * @throws IntegrationNotFoundException
     */
    private function getClient(): ClientInterface
    {
        $credentials = $this->getCredentials();
        $config      = $this->getConfig();
        /*   $client = $this->httpFactory->getClient($credentials, $config);
           if (!$this->bearer) {
               $client->request(
                   'POST',
                   $credentials->getAuthorizationUrl(),
                   [
                       'userName'    => 'WebAPI',
                       'password'    => 'SHjXz5TR',
                       'projectCode' => 'GDYU',
                       'scope'       => 'API',
                   ]
               );
           }*/
        //return $this->httpFactory->getClient($credentials, $config);

        if (!$this->isAuthenticated()) {
            $client  = new \GuzzleHttp\Client();
            $reponse = $client->post(
                $credentials->getAuthorizationUrl(),
                [
                    'json' => [
                        'username'    => $credentials->getUserName(),
                        'password'    => $credentials->getPassword(),
                        'projectCode' => $credentials->getProjectCode(),
                        'scope'       => 'API', // optional
                    ],
                ]
            );
            $response     = json_decode(($reponse->getBody()->getContents()));
            $this->bearer = $response->authToken;
        }

        return new \GuzzleHttp\Client([
            'headers' => [
                'Authorization'      => 'Bearer '.$this->bearer,
            ],
        ]);
    }

    private function isAuthenticated(): bool
    {
        return !is_null($this->bearer);
    }
}
