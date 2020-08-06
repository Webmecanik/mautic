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

use Mautic\IntegrationsBundle\Entity\ObjectMapping;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\UpdatedObjectMappingDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\FieldDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\ObjectDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\ReportDAO;
use Mautic\IntegrationsBundle\Sync\Helper\MappingHelper;
use Mautic\IntegrationsBundle\Sync\Logger\DebugLogger;
use Mautic\IntegrationsBundle\Sync\Notification\Handler\ContactNotificationHandler;
use Mautic\IntegrationsBundle\Sync\ValueNormalizer\ValueNormalizerInterface;
use MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidObjectException;
use MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidObjectValueException;
use MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidQueryArgumentException;
use MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SkipSyncException;
use MauticPlugin\MauticYellowboxCrmBundle\Exceptions\YellowboxPluginException;
use MauticPlugin\MauticYellowboxCrmBundle\Integration\Provider\YellowboxSettingProvider;
use MauticPlugin\MauticYellowboxCrmBundle\Integration\YellowboxCrmIntegration;
use MauticPlugin\MauticYellowboxCrmBundle\Mapping\ObjectFieldMapper;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\Contact;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\Validator\ContactValidator;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository\ContactRepository;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository\Mapping\ModelFactory;

class ContactDataExchange extends GeneralDataExchange
{
    /**
     * @var string
     */
    public const OBJECT_NAME = 'contact';

    /**
     * @var string
     */
    public const OBJECT_LABEL = 'Contacts';

    /**
     * @var int
     */
    private const YELLOWBOX_API_QUERY_LIMIT = 100;

    /**
     * @var ContactRepository
     */
    private $contactRepository;

    /**
     * @var ContactValidator
     */
    private $contactValidator;

    /**
     * @var ModelFactory
     */
    private $modelFactory;

    public function __construct(
        YellowboxSettingProvider $yellowboxSettingProvider,
        ValueNormalizerInterface $valueNormalizer,
        ContactRepository $contactRepository,
        ContactValidator $contactValidator,
        MappingHelper $mappingHelper,
        ObjectFieldMapper $objectFieldMapper,
        ModelFactory $modelFactory,
        ContactNotificationHandler $notificationHandler
    ) {
        parent::__construct($yellowboxSettingProvider, $valueNormalizer, $notificationHandler);
        $this->contactRepository = $contactRepository;
        $this->contactValidator  = $contactValidator;
        $this->modelFactory      = $modelFactory;
    }

    /**
     * @throws \Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException
     * @throws \Mautic\IntegrationsBundle\Sync\Exception\ObjectNotFoundException
     * @throws \Mautic\IntegrationsBundle\Sync\Exception\ObjectNotSupportedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidQueryArgumentException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     * @throws YellowboxPluginException
     */
    public function getObjectSyncReport(
        \Mautic\IntegrationsBundle\Sync\DAO\Sync\Request\ObjectDAO $requestedObject,
        ReportDAO $syncReport
    ): ReportDAO {
        $fromDateTime = $requestedObject->getFromDateTime();
        $mappedFields = $requestedObject->getFields();
        $objectFields = $this->contactRepository->describe()->getFields();

        $updated = $this->getReportPayload($fromDateTime, $mappedFields, self::OBJECT_NAME);
        /** @var Contact $object */
        foreach ($updated as $object) {
            $objectDAO = new ObjectDAO(self::OBJECT_LABEL, $object->getId(), new \DateTimeImmutable($object->getModifiedTime()->format('r')));
            foreach ($object->dehydrate($mappedFields) as $field => $value) {
                try {
                    $this->getValidator()->validateRequiredField($field, $value);
                    // Normalize the value from the API to what Mautic needs
                    $normalizedValue = $this->valueNormalizer->normalizeForMauticTyped($objectFields[$field], $value);
                    $reportFieldDAO  = new FieldDAO($field, $normalizedValue);

                    $objectDAO->addField($reportFieldDAO);
                } catch (InvalidQueryArgumentException $e) {
                    DebugLogger::log(YellowboxCrmIntegration::NAME,
                        sprintf('%s for %s %s', $e->getMessage(), self::OBJECT_NAME, $object->getId())
                    );
                    printf("%s for %s %s\n", $e->getIncomingMessage(), self::OBJECT_NAME, $object->getId());
                } catch (InvalidObjectValueException $e) {
                    DebugLogger::log(YellowboxCrmIntegration::NAME, $e->getMessage());
                    continue 2;
                } catch (InvalidObjectException $e) {
                    DebugLogger::log(YellowboxCrmIntegration::NAME, $e->getMessage());
                    continue 2;
                } catch (SkipSyncException $e) {
                    DebugLogger::log(YellowboxCrmIntegration::NAME, $e->getMessage());
                    continue;
                }
            }

            $syncReport->addObject($objectDAO);
        }

        return $syncReport;
    }

    /**
     * @param ObjectChangeDAO[] $objects
     *
     * @return UpdatedObjectMappingDAO[]
     *
     * @throws YellowboxPluginException
     * @throws \Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidObjectException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidQueryArgumentException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     */
    public function update(array $ids, array $objects): array
    {
        return $this->updateInternal($ids, $objects, self::OBJECT_NAME);
    }

    /**
     * @param ObjectChangeDAO[] $objects
     *
     * @return array|ObjectMapping[]
     *
     * @throws YellowboxPluginException
     * @throws \Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidObjectException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidQueryArgumentException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     */
    public function insert(array $objects): array
    {
        if (!$this->yellowboxSettingProvider->shouldBeMauticContactPushedAsContact()) {
            return [];
        }

        return $this->insertInternal($objects, self::OBJECT_NAME);
    }

    protected function getModel(array $objectData): Contact
    {
        $objectFields     = $this->contactRepository->describe()->getFields();
        $normalizedFields = [];

        /**
         * @var string
         * @var FieldDAO $fieldDAO
         */
        foreach ($objectData as $key => $fieldDAO) {
            $normalizedFields[$key] = $this->valueNormalizer->normalizeForYellowbox($objectFields[$fieldDAO->getName()], $fieldDAO);
        }

        return $this->modelFactory->createContact($normalizedFields);
    }

    protected function getValidator(): ContactValidator
    {
        return $this->contactValidator;
    }

    protected function getRepository(): ContactRepository
    {
        return $this->contactRepository;
    }

    protected function getYellowboxApiQueryLimit(): int
    {
        return self::YELLOWBOX_API_QUERY_LIMIT;
    }
}
