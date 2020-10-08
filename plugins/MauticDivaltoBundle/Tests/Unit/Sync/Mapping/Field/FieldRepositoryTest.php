<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDivaltoBundle\Tests\Unit\Sync\Mapping\Field;

use Mautic\CoreBundle\Helper\CacheStorageHelper;
use MauticPlugin\MauticDivaltoBundle\Connection\Client;
use MauticPlugin\MauticDivaltoBundle\Sync\Mapping\Field\Field;
use MauticPlugin\MauticDivaltoBundle\Sync\Mapping\Field\FieldRepository;
use MauticPlugin\MauticDivaltoBundle\Sync\Mapping\Manual\MappingManualFactory;

class FieldRepositoryTest extends \PHPUnit\Framework\TestCase
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

    protected function setUp(): void
    {
        $this->client               = $this->createMock(Client::class);
        $this->cacheStorageProvider = $this->createMock(CacheStorageHelper::class);
        $this->fieldRepository      = new FieldRepository($this->cacheStorageProvider, $this->client);
    }

    public function testFieldsAreFetchedFromCache(): void
    {
        $contactFields = json_decode(file_get_contents(__DIR__.'/../../../Connection/json/contacts_fields.json'), true);

        $this->cacheStorageProvider->expects($this->once())
            ->method('get')
            ->with('divalto.fields.'.MappingManualFactory::CONTACT_OBJECT)
            ->willReturn($contactFields);

        $fields = $this->fieldRepository->getFields(MappingManualFactory::CONTACT_OBJECT);
        $this->assertCount(6, $fields);

        $this->assertInstanceOf(Field::class, $fields['id']);
    }

    public function testFieldsAreFetchedLiveIfCacheIsNotAvailable(): void
    {
        $contactFields = json_decode(file_get_contents(__DIR__.'/../../../Connection/json/contacts_fields.json'), true);

        $this->cacheStorageProvider->expects($this->once())
            ->method('get')
            ->with('divalto.fields.'.MappingManualFactory::CONTACT_OBJECT)
            ->willReturn([]);

        $this->client->expects($this->once())
            ->method('getFields')
            ->with(MappingManualFactory::CONTACT_OBJECT)
            ->willReturn($contactFields);

        $fields = $this->fieldRepository->getFields(MappingManualFactory::CONTACT_OBJECT);
        $this->assertCount(6, $fields);

        $this->assertInstanceOf(Field::class, $fields['id']);
    }

    public function testGettingRequiredFieldsForMapping(): void
    {
        $contactFields = json_decode(file_get_contents(__DIR__.'/../../../Connection/json/contacts_fields.json'), true);

        $this->cacheStorageProvider->expects($this->never())
            ->method('get');

        $this->client->expects($this->once())
            ->method('getFields')
            ->with(MappingManualFactory::CONTACT_OBJECT)
            ->willReturn($contactFields);

        $fields = $this->fieldRepository->getRequiredFieldsForMapping(MappingManualFactory::CONTACT_OBJECT);
        $this->assertCount(2, $fields);

        $this->assertTrue(isset($fields['email']));
        $this->assertTrue(isset($fields['lastname']));
    }

    public function testGettingOptionalFieldsForMapping(): void
    {
        $contactFields = json_decode(file_get_contents(__DIR__.'/../../../Connection/json/contacts_fields.json'), true);

        $this->cacheStorageProvider->expects($this->never())
            ->method('get');

        $this->client->expects($this->once())
            ->method('getFields')
            ->with(MappingManualFactory::CONTACT_OBJECT)
            ->willReturn($contactFields);

        $fields = $this->fieldRepository->getOptionalFieldsForMapping(MappingManualFactory::CONTACT_OBJECT);
        $this->assertCount(4, $fields);

        $this->assertTrue(!isset($fields['email']));
        $this->assertTrue(!isset($fields['lastname']));
    }
}
