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

namespace MauticPlugin\MauticYellowboxCrmBundle\Sync;

use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\OrderDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\ReportDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Request\RequestDAO;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectNotSupportedException;
use Mautic\IntegrationsBundle\Sync\Notification\Handler\ContactNotificationHandler;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\SyncDataExchangeInterface;
use MauticPlugin\MauticYellowboxCrmBundle\Integration\YellowboxCrmIntegration;
use MauticPlugin\MauticYellowboxCrmBundle\Mapping\ObjectFieldMapper;

class DataExchange implements SyncDataExchangeInterface
{
    /**
     * @var ObjectFieldMapper
     */
    private $fieldMapper;

    /**
     * @var ContactDataExchange
     */
    private $contactDataExchange;

    /**
     * @var LeadDataExchange
     */
    private $leadDataExchange;

    /**
     * @var AccountDataExchange
     */
    private $accountDataExchange;

    public function __construct(
        ObjectFieldMapper $fieldMapper,
        ContactDataExchange $contactDataExchange,
        LeadDataExchange $leadDataExchange,
        AccountDataExchange $accountDataExchange,
        ContactNotificationHandler $contactNotificationHandler
    ) {
        $this->fieldMapper                = $fieldMapper;
        $this->contactDataExchange        = $contactDataExchange;
        $this->leadDataExchange           = $leadDataExchange;
        $this->accountDataExchange        = $accountDataExchange;
    }

    public function getFieldMapper(): ObjectFieldMapper
    {
        return $this->fieldMapper;
    }

    public function setFieldMapper(ObjectFieldMapper $fieldMapper): DataExchange
    {
        $this->fieldMapper = $fieldMapper;

        return $this;
    }

    /**
     * Get Sync report from integration.
     *
     * @throws ObjectNotSupportedException
     */
    public function getSyncReport(RequestDAO $requestDAO): ReportDAO
    {
        // Build a report of objects that have been modified
        $syncReport = new ReportDAO(YellowboxCrmIntegration::NAME);

        if ($requestDAO->getSyncIteration() > 1) {
            // Prevent loop
            return $syncReport;
        }

        $requestedObjects = $requestDAO->getObjects();

        foreach ($requestedObjects as $requestedObject) {
            $objectName = $requestedObject->getObject();

            $exchangeService = $this->getDataExchangeService($objectName);

            $syncReport = $exchangeService->getObjectSyncReport($requestedObject, $syncReport);
        }

        return $syncReport;
    }

    /**
     * Sync to integration.
     *
     * @throws ObjectNotSupportedException
     */
    public function executeSyncOrder(OrderDAO $syncOrderDAO): void
    {
        $identifiedObjects   = $syncOrderDAO->getIdentifiedObjects();
        $unidentifiedObjects = $syncOrderDAO->getUnidentifiedObjects();

        foreach ($identifiedObjects as $objectName => $updateObjects) {
            $updateCount = count($updateObjects);

            if (0 === $updateCount) {
                continue;
            }

            $identifiedObjectIds = $syncOrderDAO->getIdentifiedObjectIds($objectName);
            /** @var ObjectSyncDataExchangeInterface $dataExchange */
            $dataExchange = $this->getDataExchangeService($objectName);

            $updatedObjectMappings = $dataExchange->update($identifiedObjectIds, $identifiedObjects[$objectName]);

            foreach ($updatedObjectMappings as $updateObject) {
                $syncOrderDAO->updateLastSyncDate($updateObject);
            }
        }

        foreach ($unidentifiedObjects as $objectName => $createObjects) {
            $createCount = count($createObjects);

            if (0 === $createCount) {
                continue;
            }

            /** @var ObjectSyncDataExchangeInterface $dataExchange */
            $dataExchange = $this->getDataExchangeService($objectName);

            $objectMappings = $dataExchange->insert($createObjects);

            foreach ($objectMappings as $objectMapping) {
                $syncOrderDAO->addObjectMapping(
                    $objectMapping,
                    $objectMapping->getObject(),
                    $objectMapping->getObjectId(),
                    $objectMapping->getChangeDateTime()
                );
            }
        }
    }

    /**
     * @param $objectName
     *
     * @throws ObjectNotSupportedException
     */
    private function getDataExchangeService($objectName): ObjectSyncDataExchangeInterface
    {
        switch ($objectName) {
            case 'Contacts':
                return $this->contactDataExchange;
            case 'Leads':
                return $this->leadDataExchange;
            case 'Accounts':
                return $this->accountDataExchange;
            default:
                throw new ObjectNotSupportedException(YellowboxCrmIntegration::NAME, $objectName);
        }
    }
}
