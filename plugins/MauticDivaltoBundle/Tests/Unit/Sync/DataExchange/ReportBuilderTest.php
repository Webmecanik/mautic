<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDivaltoBundle\Tests\Unit\Sync\DataExchange;

use Mautic\CoreBundle\Helper\CacheStorageHelper;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\InputOptionsDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Request\ObjectDAO;
use MauticPlugin\MauticDivaltoBundle\Connection\Client;
use MauticPlugin\MauticDivaltoBundle\Integration\Config;
use MauticPlugin\MauticDivaltoBundle\Integration\DivaltoIntegration;
use MauticPlugin\MauticDivaltoBundle\Sync\DataExchange\ReportBuilder;
use MauticPlugin\MauticDivaltoBundle\Sync\Mapping\Field\FieldRepository;
use MauticPlugin\MauticDivaltoBundle\Sync\Mapping\Manual\MappingManualFactory;

class ReportBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Client|\PHPUnit_Framework_MockObject_MockObject
     */
    private $client;

    /**
     * @var Config|\PHPUnit_Framework_MockObject_MockObject
     */
    private $config;

    /**
     * @var FieldRepository
     */
    private $fieldRepository;

    /**
     * @var CacheStorageHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cacheStorageProvider;

    /**
     * @var ReportBuilder
     */
    private $reportBuilder;

    protected function setUp(): void
    {
        $this->client               = $this->createMock(Client::class);
        $this->config               = $this->createMock(Config::class);
        $this->cacheStorageProvider = $this->createMock(CacheStorageHelper::class);
        $this->fieldRepository      = new FieldRepository($this->cacheStorageProvider, $this->client);
        $this->reportBuilder        = new ReportBuilder($this->client, $this->config, $this->fieldRepository);
    }

    public function testReportIsBuilt(): void
    {
        $contactFields   = json_decode(file_get_contents(__DIR__.'/../../Connection/json/contacts_fields.json'), true);
        $companyFields   = json_decode(file_get_contents(__DIR__.'/../../Connection/json/companys_fields.json'), true);

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

        $page             = 1;
        $options          = new InputOptionsDAO(
            [
                'integration'     => DivaltoIntegration::NAME,
                'first-time-sync' => false,
                'start-datetime'  => '2020-02-01 00:00:00',
                'end-datetime'    => '2020-02-13 00:00:00',
            ]
        );
        $requestedObjects = [
            new ObjectDAO(MappingManualFactory::CONTACT_OBJECT, $options->getStartDateTime(), $options->getEndDateTime()),
            new ObjectDAO(MappingManualFactory::COMPANY_OBJECT, $options->getStartDateTime(), $options->getEndDateTime()),
        ];

        $contactResponse   = json_decode(file_get_contents(__DIR__.'/../../Connection/json/contacts.json'), true);
        $companyResponse   = json_decode(file_get_contents(__DIR__.'/../../Connection/json/companys.json'), true);

        $this->client->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [MappingManualFactory::CONTACT_OBJECT, $options->getStartDateTime(), $options->getEndDateTime(), $page],
                [MappingManualFactory::COMPANY_OBJECT, $options->getStartDateTime(), $options->getEndDateTime(), $page]
            )->willReturnOnConsecutiveCalls(
                $contactResponse,
                $companyResponse
            );

        $report = $this->reportBuilder->build($page, $requestedObjects, $options);

        $contacts = $report->getObjects(MappingManualFactory::CONTACT_OBJECT);

        // Contact 1 has been updated
        $this->assertTrue(isset($contacts[1]));
        $this->assertEquals($contactResponse[0]['fields']['firstname'], $contacts[1]->getField('firstname')->getValue()->getNormalizedValue());
        $this->assertEquals($contactResponse[0]['fields']['lastname'], $contacts[1]->getField('lastname')->getValue()->getNormalizedValue());
        $this->assertEquals($contactResponse[0]['fields']['email'], $contacts[1]->getField('email')->getValue()->getNormalizedValue());
        $this->assertEquals((int) $contactResponse[0]['fields']['opt_in'], $contacts[1]->getField('opt_in')->getValue()->getNormalizedValue());
        $this->assertEquals($contactResponse[0]['last_modified_timestamp'], $contacts[1]->getChangeDateTime()->format('Y-m-d H:i:s'));
        // home_planet was not mapped and thus should not be included
        $this->assertTrue(empty($contacts[1]->getFields()['home_planet']));

        // Contact 2 is new
        $this->assertTrue(isset($contacts[2]));
        $this->assertEquals($contactResponse[1]['fields']['firstname'], $contacts[2]->getField('firstname')->getValue()->getNormalizedValue());
        $this->assertEquals($contactResponse[1]['fields']['lastname'], $contacts[2]->getField('lastname')->getValue()->getNormalizedValue());
        $this->assertEquals($contactResponse[1]['fields']['email'], $contacts[2]->getField('email')->getValue()->getNormalizedValue());
        $this->assertEquals((int) $contactResponse[1]['fields']['opt_in'], $contacts[2]->getField('opt_in')->getValue()->getNormalizedValue());
        $this->assertEquals($contactResponse[1]['created_timestamp'], $contacts[2]->getChangeDateTime()->format('Y-m-d H:i:s'));

        $companys = $report->getObjects(MappingManualFactory::COMPANY_OBJECT);

        // Company 1 has been updated
        $this->assertTrue(isset($companys[1]));
        $this->assertEquals($companyResponse[0]['fields']['name'], $companys[1]->getField('name')->getValue()->getNormalizedValue());
        $this->assertEquals($companyResponse[0]['fields']['type'], $companys[1]->getField('type')->getValue()->getNormalizedValue());
        $this->assertEquals($companyResponse[0]['last_modified_timestamp'], $companys[1]->getChangeDateTime()->format('Y-m-d H:i:s'));

        // Company 2 is new
        $this->assertTrue(isset($companys[2]));
        $this->assertEquals($companyResponse[1]['fields']['name'], $companys[2]->getField('name')->getValue()->getNormalizedValue());
        $this->assertEquals($companyResponse[1]['fields']['type'], $companys[2]->getField('type')->getValue()->getNormalizedValue());
        $this->assertEquals($companyResponse[1]['created_timestamp'], $companys[2]->getChangeDateTime()->format('Y-m-d H:i:s'));
    }
}
