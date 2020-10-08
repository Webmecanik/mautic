<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDivaltoBundle\Tests\Unit\Sync\Mapping\Manual;

use Mautic\CoreBundle\Helper\CacheStorageHelper;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\ObjectMappingDAO;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use MauticPlugin\MauticDivaltoBundle\Connection\Client;
use MauticPlugin\MauticDivaltoBundle\Integration\Config;
use MauticPlugin\MauticDivaltoBundle\Sync\Mapping\Field\FieldRepository;
use MauticPlugin\MauticDivaltoBundle\Sync\Mapping\Manual\MappingManualFactory;

class MappingManualFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Client|\PHPUnit_Framework_MockObject_MockObject
     */
    private $client;

    /**
     * @var FieldRepository
     */
    private $fieldRepository;

    /**
     * @var CacheStorageHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cacheStorageProvider;

    /**
     * @var Config|\PHPUnit_Framework_MockObject_MockObject
     */
    private $config;

    /**
     * @var MappingManualFactory
     */
    private $mappingManualFactory;

    protected function setUp(): void
    {
        $this->cacheStorageProvider = $this->createMock(CacheStorageHelper::class);
        $this->client               = $this->createMock(Client::class);
        $this->fieldRepository      = new FieldRepository($this->cacheStorageProvider, $this->client);
        $this->config               = $this->createMock(Config::class);
        $this->mappingManualFactory = new MappingManualFactory($this->fieldRepository, $this->config);
    }

    public function testMappingManualIsCompiledAndReturned(): void
    {
        $contactFields   = json_decode(file_get_contents(__DIR__.'/../../../Connection/json/contacts_fields.json'), true);
        $companyFields   = json_decode(file_get_contents(__DIR__.'/../../../Connection/json/companys_fields.json'), true);

        $this->cacheStorageProvider->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                ['divalto.fields.'.MappingManualFactory::CONTACT_OBJECT],
                ['divalto.fields.'.MappingManualFactory::COMPANY_OBJECT]
            )->willReturnOnConsecutiveCalls(
                $contactFields,
                $companyFields
            );

        $this->config->expects($this->exactly(2))
            ->method('getMappedFields')
            ->withConsecutive(
                [MappingManualFactory::CONTACT_OBJECT],
                [MappingManualFactory::COMPANY_OBJECT]
            )->willReturnOnConsecutiveCalls(
                [
                    'firstname' => 'first_name',
                    'lastname'  => 'last_name',
                    'email'     => 'email',
                    'opt_in'    => 'opt_in',
                ],
                [
                    'name' => 'company_name',
                    'type' => 'company_type',
                ]
            );

        $this->config->expects($this->exactly(6))
            ->method('getFieldDirection')
            ->withConsecutive(
                [MappingManualFactory::CONTACT_OBJECT, 'firstname'],
                [MappingManualFactory::CONTACT_OBJECT, 'lastname'],
                [MappingManualFactory::CONTACT_OBJECT, 'email'],
                [MappingManualFactory::CONTACT_OBJECT, 'opt_in'],
                [MappingManualFactory::COMPANY_OBJECT, 'name'],
                [MappingManualFactory::COMPANY_OBJECT, 'type']
            )->willReturnOnConsecutiveCalls(
                ObjectMappingDAO::SYNC_TO_MAUTIC,
                ObjectMappingDAO::SYNC_BIDIRECTIONALLY,
                ObjectMappingDAO::SYNC_TO_MAUTIC,
                ObjectMappingDAO::SYNC_TO_INTEGRATION,
                ObjectMappingDAO::SYNC_BIDIRECTIONALLY,
                ObjectMappingDAO::SYNC_TO_MAUTIC
            );

        $manual = $this->mappingManualFactory->getManual();

        // bidirectional and sync to mautic fields should be included
        $syncToMautic = $manual->getIntegrationObjectFieldsToSyncToMautic(MappingManualFactory::CONTACT_OBJECT);
        $this->assertTrue(in_array('firstname', $syncToMautic));
        $this->assertTrue(in_array('lastname', $syncToMautic));
        $this->assertTrue(in_array('email', $syncToMautic));
        $this->assertFalse(in_array('opt_in', $syncToMautic));

        // bidirectional and sync to integration should be in array
        $syncToIntegration = $manual->getInternalObjectFieldsToSyncToIntegration(Contact::NAME);
        $this->assertFalse(in_array('first_name', $syncToIntegration));
        $this->assertTrue(in_array('last_name', $syncToIntegration));
        // Email is included because it's required even though it is set to SYNC_TO_MAUTIC
        $this->assertTrue(in_array('email', $syncToIntegration));
        $this->assertTrue(in_array('opt_in', $syncToIntegration));
    }
}
