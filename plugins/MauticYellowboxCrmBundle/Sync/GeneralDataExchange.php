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

use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\FieldDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\NotificationDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use Mautic\IntegrationsBundle\Sync\Logger\DebugLogger;
use Mautic\IntegrationsBundle\Sync\Notification\Handler\HandlerInterface;
use Mautic\IntegrationsBundle\Sync\ValueNormalizer\ValueNormalizerInterface;
use MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidQueryArgumentException;
use MauticPlugin\MauticYellowboxCrmBundle\Exceptions\Validation\InvalidObject;
use MauticPlugin\MauticYellowboxCrmBundle\Exceptions\YellowboxPluginException;
use MauticPlugin\MauticYellowboxCrmBundle\Integration\Provider\YellowboxSettingProvider;
use MauticPlugin\MauticYellowboxCrmBundle\Integration\YellowboxCrmIntegration;
use MauticPlugin\MauticYellowboxCrmBundle\Sync\ValueNormalizer\YellowboxValueNormalizer;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\Account;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\BaseModel;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\Contact;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\Lead;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\Validator\AccountValidator;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\Validator\ContactValidator;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\Validator\LeadValidator;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\Validator\ObjectValidatorInterface;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository\AccountRepository;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository\BaseRepository;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository\ContactRepository;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository\LeadRepository;

abstract class GeneralDataExchange implements ObjectSyncDataExchangeInterface
{
    /**
     * @var YellowboxSettingProvider
     */
    protected $yellowboxSettingProvider;

    /**
     * @var YellowboxValueNormalizer
     */
    protected $valueNormalizer;

    /**
     * @var HandlerInterface
     */
    private $notificationHandler;

    public function __construct(
        YellowboxSettingProvider $yellowboxSettingProvider,
        ValueNormalizerInterface $valueNormalizer,
        HandlerInterface $notificationHandler
    ) {
        $this->yellowboxSettingProvider = $yellowboxSettingProvider;
        $this->valueNormalizer          = $valueNormalizer;
        $this->notificationHandler      = $notificationHandler;
    }

    /**
     * @param BaseModel[] $objects
     *
     * @throws InvalidQueryArgumentException
     * @throws YellowboxPluginException
     * @throws \Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidObjectException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     */
    protected function updateInternal(array $ids, array $objects, string $objectName): array
    {
        DebugLogger::log($objectName, sprintf('Found %d objects to update to integration with ids %s', count($objects), implode(', ', $ids)), __CLASS__.':'.__FUNCTION__);
        $updatedMappedObjects = [];

        /** @var ObjectChangeDAO $changedObject */
        foreach ($objects as $integrationObjectId => $changedObject) {
            $fields =  $changedObject->getChangedFields();

            $fields['ID_ELEMENT'] = new FieldDAO('ID_ELEMENT', new NormalizedValueDAO('string', $changedObject->getObjectId(), $changedObject->getObjectId()));

            $objectModel = $this->getModel($fields);

            if ($this->yellowboxSettingProvider->isOwnerUpdateEnabled()) {
                $objectModel->setAssignedUserId($this->yellowboxSettingProvider->getOwner());
            }

            /* Perform validation */
            try {
                $this->getValidator()->validate($objectModel);
            } catch (InvalidObject $e) {
                $this->logInvalidObject($changedObject, $objectName, $e);
                continue;
            }

            try {
                $this->getRepository()->update($objectModel);

                $newChange = new ObjectChangeDAO(
                    YellowboxCrmIntegration::NAME, $changedObject->getObject(), $changedObject->getObjectId(), $changedObject->getMappedObject(), $changedObject->getMappedObjectId()
                );

                $updatedMappedObjects[] = $newChange;

                DebugLogger::log(YellowboxCrmIntegration::NAME, sprintf('Updated to %s ID %s', $objectName, $integrationObjectId), __CLASS__.':'.__FUNCTION__);
            } catch (InvalidQueryArgumentException $e) {
                DebugLogger::log(YellowboxCrmIntegration::NAME, sprintf('Update to %s ID %s failed: %s', $objectName, $integrationObjectId, $e->getMessage()), __CLASS__.':'.__FUNCTION__);
            }
        }

        return $updatedMappedObjects;
    }

    /**
     * @param BaseModel[] $objects
     *
     * @return array|[]
     *
     * @throws InvalidQueryArgumentException
     * @throws YellowboxPluginException
     * @throws \Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidObjectException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     */
    protected function insertInternal(array $objects, string $objectName): array
    {
        DebugLogger::log($objectName, sprintf('Found %d %s to INSERT', $objectName, count($objects)), __CLASS__.':'.__FUNCTION__);

        $objectMappings = [];
        /** @var ObjectChangeDAO $object */
        foreach ($objects as $object) {
            $fields = $object->getFields();
            /* Perform validation */
            try {
                $objectModel = $this->getModel($fields);

                if (!$this->yellowboxSettingProvider->getOwner()) {
                    throw new YellowboxPluginException('You need to configure owner for new objects');
                }
                $objectModel->setAssignedUserId($this->yellowboxSettingProvider->getOwner());

                $this->getValidator()->validate($objectModel);
            } catch (InvalidObject $e) {
                $this->logInvalidObject($object, $objectName, $e);
                continue;
            }

            try {
                $response = $this->getRepository()->create($objectModel);

                DebugLogger::log(
                    YellowboxCrmIntegration::NAME,
                    sprintf('Created %s ID %s from %s %d', $objectName, $response->getId(), $object->getMappedObject(), $object->getMappedObjectId()), __CLASS__.':'.__FUNCTION__
                );

                $objectMapping = new ObjectChangeDAO(
                    $object->getIntegration(), $object->getObject(), $response->getId(), $object->getMappedObject(), $object->getMappedObjectId()
                );

                $objectMapping->setChangeDateTime($response->getModifiedTime());

                $objectMappings[] = $objectMapping;
            } catch (InvalidQueryArgumentException $e) {
                DebugLogger::log(YellowboxCrmIntegration::NAME, sprintf("Failed to create %s with error '%s'", $objectName, $e->getMessage()), __CLASS__.':'.__FUNCTION__);
            }
        }

        return $objectMappings;
    }

    /**
     * @return array|mixed
     *
     * @throws InvalidQueryArgumentException
     * @throws YellowboxPluginException
     * @throws \Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     */
    protected function getReportPayload(\DateTimeImmutable $fromDate, array $mappedFields, string $objectName): array
    {
        return $this->getRepository()->getResultsByField('MODIF_DATE', 'GREATER', $fromDate->format('Y-m-d H:i:s'));
    }

    private function logInvalidObject(ObjectChangeDAO $object, string $objectName, InvalidObject $exception): void
    {
        $this->notificationHandler->writeEntry(
            new NotificationDAO($object, $exception->getMessage()),
            YellowboxCrmIntegration::NAME,
            $objectName
        );

        DebugLogger::log(
            YellowboxCrmIntegration::NAME,
            sprintf(
                "Invalid object %s (%s) with ID '%s' with message '%s'",
                $objectName,
                $object->getMappedObject(),
                $object->getMappedObjectId(),
                $exception->getMessage()
            ),
            __CLASS__.':'.__FUNCTION__
        );
    }

    /**
     * @return BaseModel|Contact|Account|Lead
     */
    abstract protected function getModel(array $objectData);

    /**
     * @return ObjectValidatorInterface|LeadValidator|ContactValidator|AccountValidator
     */
    abstract protected function getValidator();

    /**
     * @return BaseRepository|LeadRepository|ContactRepository|AccountRepository
     */
    abstract protected function getRepository();

    abstract protected function getYellowboxApiQueryLimit(): int;
}
