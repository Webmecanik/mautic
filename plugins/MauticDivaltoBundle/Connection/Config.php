<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDivaltoBundle\Connection;

use kamermans\OAuth2\Persistence\TokenPersistenceInterface;
use Mautic\IntegrationsBundle\Auth\Support\Oauth2\ConfigAccess\ConfigTokenPersistenceInterface;
use Mautic\IntegrationsBundle\Auth\Support\Oauth2\Token\TokenPersistenceFactory;
use Mautic\PluginBundle\Entity\Integration;

class Config implements ConfigTokenPersistenceInterface
{
    /**
     * @var TokenPersistenceFactory
     */
    private $tokenPersistenceFactory;

    /**
     * @var Integration
     */
    private $integrationConfiguration;

    public function __construct(TokenPersistenceFactory $tokenPersistenceFactory)
    {
        $this->tokenPersistenceFactory = $tokenPersistenceFactory;
    }

    public function getTokenPersistence(): TokenPersistenceInterface
    {
        return $this->tokenPersistenceFactory->create($this->integrationConfiguration);
    }

    public function setIntegrationConfiguration(Integration $integrationConfiguration): void
    {
        $this->integrationConfiguration = $integrationConfiguration;

        // MOCKED SINCE THE PLUGIN CANNOT ACTUALLY FETCH AN ACCESS TOKEN; THIS IS NOT NEEDED IN YOUR PLUGIN
        $apiKeys                        = $integrationConfiguration->getApiKeys();
        $apiKeys                        = array_merge(
            $apiKeys,
            [
                'authToken'  => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJqdGkiOiIzNDNmNDNjOS0yNWIxLTQ0OTAtOGNmMi05MzE3N2E3MjgxNjQiLCJpYXQiOjE1OTAzMzMxNzIsInByb2plY3RfY29kZSI6IkdEWVUiLCJjb21wYW55X2NvZGUiOiJHRFlVIiwiY29tcGFueV9uYW1lIjoiR0RZVSIsInNjb3BlIjoiMiIsImNvbXBhbnlfaWQiOiIyIiwiZGV2aWNlX2lkIjoiMjY0IiwidXNlcm5hbWUiOiJXZWJBUEkiLCJyb2xlcyI6IlVzZXIiLCJhY2NvdW50X3N0YXR1cyI6IjAiLCJhY2NvdW50X3R5cGUiOiI0IiwiYWNjZXNzX3R5cGUiOiIyIiwibmJmIjoxNTkwMzMzMTcyLCJleHAiOjE1OTA1MDU5NzIsImlzcyI6ImFwaS53ZWF2eS5kaXZhbHRvLmNvbSIsImF1ZCI6ImRpdmFsdG8uY29tIn0.2rwLxRwjx3-0SUmozj5VxslLVPGmmXs79XmjFuSYu9I',
            ]
        );
        $integrationConfiguration->setApiKeys($apiKeys);
    }
}
