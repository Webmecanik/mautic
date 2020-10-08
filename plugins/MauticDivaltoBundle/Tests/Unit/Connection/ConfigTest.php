<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDivaltoBundle\Tests\Unit\Connection;

use Mautic\IntegrationsBundle\Auth\Support\Oauth2\Token\TokenPersistence;
use Mautic\IntegrationsBundle\Auth\Support\Oauth2\Token\TokenPersistenceFactory;
use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\MauticDivaltoBundle\Connection\Config;

class ConfigTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TokenPersistenceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $tokenPersistanceFactory;

    protected function setUp(): void
    {
        $this->tokenPersistanceFactory = $this->createMock(TokenPersistenceFactory::class);
    }

    public function testTokenPersistanceInterfaceIsReturned(): void
    {
        $integrationConfiguration = new Integration();
        $tokenPersistance         = $this->createMock(TokenPersistence::class);

        $this->tokenPersistanceFactory->expects($this->once())
            ->method('create')
            ->with($integrationConfiguration)
            ->willReturn($tokenPersistance);

        $config = new Config($this->tokenPersistanceFactory);
        $config->setIntegrationConfiguration($integrationConfiguration);

        $returnedTokenPersistance = $config->getTokenPersistence();

        $this->assertSame($tokenPersistance, $returnedTokenPersistance);
    }
}
