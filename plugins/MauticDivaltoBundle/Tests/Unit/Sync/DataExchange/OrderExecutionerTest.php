<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDivaltoBundle\Tests\Unit\Sync\DataExchange;

use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\FieldDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\OrderDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Company;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use MauticPlugin\MauticDivaltoBundle\Connection\Client;
use MauticPlugin\MauticDivaltoBundle\Integration\DivaltoIntegration;
use MauticPlugin\MauticDivaltoBundle\Sync\DataExchange\OrderExecutioner;
use MauticPlugin\MauticDivaltoBundle\Sync\Mapping\Manual\MappingManualFactory;

class OrderExecutionerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Client|\PHPUnit_Framework_MockObject_MockObject
     */
    private $client;

    private $mauticObjects = [
        // Correlates with contacts_upsert.json and contacts_upsert_responses.json
        Contact::NAME => [
            10 => [
                'id'     => 1,
                'object' => MappingManualFactory::CONTACT_OBJECT,
                'fields' => [
                    [
                        'name'  => 'firstname',
                        'value' => 'Alien1',
                        'type'  => NormalizedValueDAO::TEXT_TYPE,
                    ],
                ],
            ],
            20 => [
                'id'     => 2,
                'object' => MappingManualFactory::CONTACT_OBJECT,
                'fields' => [
                    [
                        'name'  => 'firstname',
                        'value' => 'Alien2',
                        'type'  => NormalizedValueDAO::TEXT_TYPE,
                    ],
                ],
            ],
            30 => [
                'id'     => '',
                'object' => MappingManualFactory::CONTACT_OBJECT,
                'fields' => [
                    [
                        'name'  => 'firstname',
                        'value' => 'Charlie',
                        'type'  => NormalizedValueDAO::TEXT_TYPE,
                    ],
                    [
                        'name'  => 'lastname',
                        'value' => 'Alien',
                        'type'  => NormalizedValueDAO::TEXT_TYPE,
                    ],
                    [
                        'name'  => 'email',
                        'value' => 'charlie@faraway.com',
                        'type'  => NormalizedValueDAO::EMAIL_TYPE,
                    ],
                    [
                        'name'  => 'opt_in',
                        'value' => 1,
                        'type'  => NormalizedValueDAO::BOOLEAN_TYPE,
                    ],
                ],
            ],
            40 => [
                'id'     => '',
                'object' => MappingManualFactory::CONTACT_OBJECT,
                'fields' => [
                    [
                        'name'  => 'firstname',
                        'value' => 'Feliz',
                        'type'  => NormalizedValueDAO::TEXT_TYPE,
                    ],
                    [
                        'name'  => 'lastname',
                        'value' => 'Alien',
                        'type'  => NormalizedValueDAO::TEXT_TYPE,
                    ],
                    [
                        'name'  => 'email',
                        'value' => '',
                        'type'  => NormalizedValueDAO::EMAIL_TYPE,
                    ],
                    [
                        'name'  => 'opt_in',
                        'value' => 0,
                        'type'  => NormalizedValueDAO::BOOLEAN_TYPE,
                    ],
                ],
            ],
            50 => [
                'id'     => '',
                'object' => MappingManualFactory::CONTACT_OBJECT,
                'fields' => [
                    [
                        'name'  => 'firstname',
                        'value' => 'Pancho',
                        'type'  => NormalizedValueDAO::TEXT_TYPE,
                    ],
                    [
                        'name'  => 'lastname',
                        'value' => 'Alien',
                        'type'  => NormalizedValueDAO::TEXT_TYPE,
                    ],
                    [
                        'name'  => 'email',
                        'value' => 'pancho@faraway.com',
                        'type'  => NormalizedValueDAO::EMAIL_TYPE,
                    ],
                    [
                        'name'  => 'opt_in',
                        'value' => 1,
                        'type'  => NormalizedValueDAO::BOOLEAN_TYPE,
                    ],
                ],
            ],
        ],
        // Correlates with companys_upsert.json and companys_upsert_responses.json
        Company::NAME => [
            10 => [
                'id'     => 1,
                'object' => MappingManualFactory::COMPANY_OBJECT,
                'fields' => [
                    [
                        'name'  => 'type',
                        'value' => 'rock',
                        'type'  => NormalizedValueDAO::TEXT_TYPE,
                    ],
                ],
            ],
            20 => [
                'id'     => '',
                'object' => MappingManualFactory::COMPANY_OBJECT,
                'fields' => [
                    [
                        'name'  => 'name',
                        'value' => 'Saturn',
                        'type'  => NormalizedValueDAO::TEXT_TYPE,
                    ],
                    [
                        'name'  => 'type',
                        'value' => 'gas',
                        'type'  => NormalizedValueDAO::TEXT_TYPE,
                    ],
                ],
            ],
        ],
    ];

    protected function setUp(): void
    {
        $this->client = $this->createMock(Client::class);
    }

    public function testOrderIsExecuted(): void
    {
        $contactPayload  = json_decode(file_get_contents(__DIR__.'/../../Connection/json/contacts_upsert.json'), true);
        $contactResponse = json_decode(file_get_contents(__DIR__.'/../../Connection/json/contacts_upsert_response.json'), true);

        $companyPayload  = json_decode(file_get_contents(__DIR__.'/../../Connection/json/companys_upsert.json'), true);
        $companyResponse = json_decode(file_get_contents(__DIR__.'/../../Connection/json/companys_upsert_response.json'), true);

        $this->client->expects($this->exactly(2))
            ->method('upsert')
            ->withConsecutive(
                [MappingManualFactory::CONTACT_OBJECT, $contactPayload],
                [MappingManualFactory::COMPANY_OBJECT, $companyPayload]
            )->willReturnOnConsecutiveCalls(
                $contactResponse,
                $companyResponse
            );

        $order       = $this->createOrder();
        $executioner = new OrderExecutioner($this->client);

        $executioner->execute($order);

        $updated            = $order->getUpdatedObjectMappings();
        $expectedContacts   = [1];
        $foundContacts      = [];
        $expectedCompanys   = [1];
        $foundCompanys      = [];
        foreach ($updated as $objectMappingDAO) {
            if (MappingManualFactory::CONTACT_OBJECT === $objectMappingDAO->getIntegrationObjectName()) {
                $foundContacts[] = $objectMappingDAO->getIntegrationObjectId();
            }

            if (MappingManualFactory::COMPANY_OBJECT === $objectMappingDAO->getIntegrationObjectName()) {
                $foundCompanys[] = $objectMappingDAO->getIntegrationObjectId();
            }
        }
        $this->assertSame($expectedContacts, $foundContacts);
        $this->assertSame($expectedCompanys, $foundCompanys);

        $created            = $order->getObjectMappings();
        $expectedContacts   = [3];
        $foundContacts      = [];
        $expectedCompanys   = [3];
        $foundCompanys      = [];
        foreach ($created as $objectMappingDAO) {
            if (MappingManualFactory::CONTACT_OBJECT === $objectMappingDAO->getIntegrationObjectName()) {
                $foundContacts[] = $objectMappingDAO->getIntegrationObjectId();
            }

            if (MappingManualFactory::COMPANY_OBJECT === $objectMappingDAO->getIntegrationObjectName()) {
                $foundCompanys[] = $objectMappingDAO->getIntegrationObjectId();
            }
        }
        $this->assertSame($expectedContacts, $foundContacts);
        $this->assertSame($expectedCompanys, $foundCompanys);

        $deleted            = $order->getDeletedObjects();
        $expectedContacts   = [2];
        $foundContacts      = [];
        $expectedCompanys   = [];
        $foundCompanys      = [];
        foreach ($deleted as $objectChangeDAO) {
            if (MappingManualFactory::CONTACT_OBJECT === $objectChangeDAO->getObject()) {
                $foundContacts[] = $objectChangeDAO->getObjectId();
            }

            if (MappingManualFactory::COMPANY_OBJECT === $objectChangeDAO->getObject()) {
                $foundCompanys[] = $objectChangeDAO->getObjectId();
            }
        }
        $this->assertSame($expectedContacts, $foundContacts);
        $this->assertSame($expectedCompanys, $foundCompanys);

        $notifications     = $order->getNotifications();
        $expectedContacts  = [40 => 'Email is required'];
        $foundContacts     = [];
        $expectedCompanies = [];
        $foundCompanies    = [];
        foreach ($notifications as $notificationDAO) {
            if (MappingManualFactory::CONTACT_OBJECT === $notificationDAO->getIntegrationObject()) {
                $foundContacts[$notificationDAO->getMauticObjectId()] = $notificationDAO->getMessage();
            }

            if (MappingManualFactory::COMPANY_OBJECT === $notificationDAO->getIntegrationObject()) {
                $foundCompanies[$notificationDAO->getMauticObjectId()] = $notificationDAO->getMessage();
            }
        }
        $this->assertSame($expectedContacts, $foundContacts);
        $this->assertSame($expectedCompanies, $foundCompanies);

        // Retries should not be included in any of the above
    }

    private function createOrder(): OrderDAO
    {
        $order = new OrderDAO(new \DateTime(), false, DivaltoIntegration::NAME);

        foreach ($this->mauticObjects as $mappedObjectName => $objects) {
            foreach ($objects as $mappedId => $object) {
                $objectChangeDAO = new ObjectChangeDAO(
                    DivaltoIntegration::NAME,
                    $object['object'],
                    $object['id'],
                    $mappedObjectName,
                    $mappedId
                );

                foreach ($object['fields'] as $field) {
                    $objectChangeDAO->addField(
                        new FieldDAO($field['name'], new NormalizedValueDAO($field['type'], $field['value']))
                    );
                }

                $order->addObjectChange($objectChangeDAO);
            }
        }

        return $order;
    }
}
