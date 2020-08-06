<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc. Jan Kozak <galvani78@gmail.com>
 *
 * @link        http://mautic.com
 * @created     8.11.18
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticYellowboxCrmBundle\Tests\Sync;

use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\OrderDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\FieldDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\ReportDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Request\ObjectDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Request\RequestDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use Mautic\IntegrationsBundle\Sync\Notification\Handler\ContactNotificationHandler;
use MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidObjectValueException;
use MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidQueryArgumentException;
use MauticPlugin\MauticYellowboxCrmBundle\Integration\Provider\YellowboxSettingProvider;
use MauticPlugin\MauticYellowboxCrmBundle\Integration\YellowboxCrmIntegration;
use MauticPlugin\MauticYellowboxCrmBundle\Mapping\ObjectFieldMapper;
use MauticPlugin\MauticYellowboxCrmBundle\Sync\AccountDataExchange;
use MauticPlugin\MauticYellowboxCrmBundle\Sync\ContactDataExchange;
use MauticPlugin\MauticYellowboxCrmBundle\Sync\DataExchange;
use MauticPlugin\MauticYellowboxCrmBundle\Sync\LeadDataExchange;
use MauticPlugin\MauticYellowboxCrmBundle\Tests\TestDataProvider\ModulesDescriptionProvider;
use MauticPlugin\MauticYellowboxCrmBundle\Tests\TestDataProvider\YellowboxContactTestDataProvider;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Connection;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\Contact;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository\AccountRepository;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository\ContactRepository;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository\LeadRepository;

class DataExchangeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DataExchange
     */
    private $dataExchange;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|AccountRepository
     */
    private $mockAccountRepository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ContactRepository
     */
    private $mockContactRepository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|LeadRepository
     */
    private $mockLeadRepository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Connection
     */
    private $mockConnection;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|YellowboxSettingProvider
     */
    private $mockSettingsProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ContactDataExchange
     */
    private $contactDataExchange;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|LeadDataExchange
     */
    private $leadDataExchange;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|AccountDataExchange
     */
    private $accountDataExchange;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ContactNotificationHandler
     */
    private $contactNotificationHandler;

    /**
     * @var RequestDAO
     */
    private $requestDAO;

    /**
     * @var ReportDAO
     */
    private $contactReport;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $leadModuleInfo = ModulesDescriptionProvider::getLead();
        $leadFields     = array_keys($leadModuleInfo->getFields());

        $this->mockConnection        = $this->createMock(Connection::class);
        $this->mockSettingsProvider  = $this->createMock(YellowboxSettingProvider::class);
        $this->mockContactRepository = $this->createMock(ContactRepository::class);
        $this->mockContactRepository->method('describe')->willReturn($leadModuleInfo);

        $this->mockLeadRepository    = $this->createMock(LeadRepository::class);
        $this->mockLeadRepository->method('describe')->willReturn($leadModuleInfo);

        $this->mockAccountRepository = $this->createMock(AccountRepository::class);

        $objectFieldMapper = new ObjectFieldMapper(
            $this->mockSettingsProvider,
            $this->mockContactRepository,
            $this->mockLeadRepository,
            $this->mockAccountRepository
        );

        $this->contactDataExchange = $this->createMock(ContactDataExchange::class);
        $this->contactDataExchange->method('getObjectSyncReport')->willReturnCallback(function ($requestedObject, $syncReport) {
            $updates = YellowboxContactTestDataProvider::getYellowboxContacts();
            /** @var Contact $object */
            foreach ($updates as $object) {
                $objectDAO = new \Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\ObjectDAO(
                    ContactDataExchange::OBJECT_NAME, $object->getId(),
                    new \DateTimeImmutable($object->getModifiedTime()->format('r'))
                );

                foreach ($object->dehydrate([]) as $field => $value) {
                    try {
                        $reportFieldDAO = new FieldDAO($field, new NormalizedValueDAO('string', $value, $value));

                        $objectDAO->addField($reportFieldDAO);
                    } catch (InvalidQueryArgumentException $e) {
                    } catch (InvalidObjectValueException $exception) {
                        continue 2;
                    }
                }

                $syncReport->addObject($objectDAO);
            }

            return $syncReport;
        });
        $this->contactDataExchange->method('update')->with($this->callback(function ($objects) {
            $identified = $this->getSyncOrder(ContactDataExchange::OBJECT_NAME)->getIdentifiedObjects()[ContactDataExchange::OBJECT_NAME];

            return 2 === count($identified);
        }));
        $this->contactDataExchange->method('insert')->with($this->callback(function ($objects) {
            $unindentified = $this->getSyncOrder(ContactDataExchange::OBJECT_NAME)->getUnidentifiedObjects()[ContactDataExchange::OBJECT_NAME];

            return 1 === count($unindentified);
        }));

        $this->leadDataExchange           = $this->createMock(LeadDataExchange::class);
        $this->accountDataExchange        = $this->createMock(AccountDataExchange::class);
        $this->contactNotificationHandler = $this->createMock(ContactNotificationHandler::class);

        $this->dataExchange = new DataExchange(
            $objectFieldMapper,
            $this->contactDataExchange,
            $this->leadDataExchange,
            $this->accountDataExchange,
            $this->contactNotificationHandler
        );

        $this->requestDAO = new RequestDAO(0, false, YellowboxCrmIntegration::NAME);
        $this->requestDAO->addObject(new ObjectDAO(ContactDataExchange::OBJECT_NAME));
    }

    public function testSyncReportIsBuiltCorrectlyForContacts()
    {
        $this->contactReport = $this->dataExchange->getSyncReport($this->requestDAO);
        $contactReport       = $this->contactReport->getObjects(ContactDataExchange::OBJECT_NAME);

        $updates = YellowboxContactTestDataProvider::getYellowboxContacts();

        $contactArray = [];
        $updatesArray = [];

        /** @var \Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\ObjectDAO $contact */
        foreach ($contactReport as $contact) {
            foreach ($contact->getFields() as $field) {
                $contactArray[$contact->getObjectId()][$field->getName()] = $field->getValue()->getNormalizedValue();
            }
            asort($contactArray[$contact->getObjectId()]);
        }

        foreach ($updates as $update) {
            $dehydrated = $update->dehydrate([]);
            asort($dehydrated);
            $updatesArray[$dehydrated['id']] = $dehydrated;
        }

        $this->assertEquals($contactArray, $updatesArray);
    }

    public function testExecuteSyncOrderContact()
    {
        $this->contactDataExchange->expects($this->exactly(1))->method('update');
        $this->contactDataExchange->expects($this->exactly(1))->method('insert');

        $syncOrder = $this->getSyncOrder(ContactDataExchange::OBJECT_NAME);
        $this->dataExchange->executeSyncOrder($syncOrder);
    }

    /**
     * @param $objectName
     *
     * @return OrderDAO
     *
     * @throws \Exception
     */
    private function getSyncOrder($objectName)
    {
        $integration = YellowboxCrmIntegration::NAME;

        $syncOrder = new OrderDAO(new \DateTimeImmutable(), false, $integration);

        // Two updates
        $syncOrder->addObjectChange(new ObjectChangeDAO($integration, $objectName, 1, $objectName, 1));
        $syncOrder->addObjectChange(new ObjectChangeDAO($integration, $objectName, 2, $objectName, 2));

        // One create
        $syncOrder->addObjectChange(new ObjectChangeDAO($integration, $objectName, null, $objectName, 3));

        return $syncOrder;
    }
}
