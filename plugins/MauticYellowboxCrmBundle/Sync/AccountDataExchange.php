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

use Mautic\IntegrationsBundle\Sync\DAO\Mapping\UpdatedObjectMappingDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\FieldDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\ObjectDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\ReportDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use Mautic\IntegrationsBundle\Sync\Logger\DebugLogger;
use Mautic\IntegrationsBundle\Sync\Notification\Handler\CompanyNotificationHandler;
use Mautic\IntegrationsBundle\Sync\ValueNormalizer\ValueNormalizerInterface;
use MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidObjectException;
use MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidObjectValueException;
use MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidQueryArgumentException;
use MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SkipSyncException;
use MauticPlugin\MauticYellowboxCrmBundle\Exceptions\YellowboxPluginException;
use MauticPlugin\MauticYellowboxCrmBundle\Integration\Provider\YellowboxSettingProvider;
use MauticPlugin\MauticYellowboxCrmBundle\Integration\YellowboxCrmIntegration;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\Account;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\BaseModel;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\Validator\AccountValidator;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository\AccountRepository;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository\Mapping\ModelFactory;

/**
 * This synchronizes data between Yellowbox Organization named on API as Account and in Mautic named as Company.
 */
class AccountDataExchange extends GeneralDataExchange
{
    /**
     * @var string
     */
    public const OBJECT_NAME = 'societe';

    /**
     * @var string
     */
    public const OBJECT_LABEL = 'Accounts';

    /**
     * @var int
     */
    private const YELLOWBOX_API_QUERY_LIMIT = 100;

    /**
     * @var AccountRepository
     */
    private $accountRepository;

    /**
     * @var AccountValidator
     */
    private $accountValidator;

    /**
     * @var ModelFactory
     */
    private $modelFactory;

    public function __construct(
        YellowboxSettingProvider $yellowboxSettingProvider,
        ValueNormalizerInterface $valueNormalizer,
        AccountRepository $accountRepository,
        AccountValidator $accountValidator,
        ModelFactory $modelFactory,
        CompanyNotificationHandler $notificationHandler
    ) {
        parent::__construct($yellowboxSettingProvider, $valueNormalizer, $notificationHandler);
        $this->accountRepository = $accountRepository;
        $this->accountValidator  = $accountValidator;
        $this->modelFactory      = $modelFactory;
    }

    /**
     * @throws \Exception
     */
    public function getObjectSyncReport(
        \Mautic\IntegrationsBundle\Sync\DAO\Sync\Request\ObjectDAO $requestedObject,
        ReportDAO $syncReport
    ): ReportDAO {
        $fromDateTime = $requestedObject->getFromDateTime();
        $mappedFields = $requestedObject->getFields();
        $objectFields = $this->accountRepository->describe()->getFields();

        $updated = $this->getReportPayload($fromDateTime, $mappedFields, self::OBJECT_NAME);

        /** @var BaseModel $object */
        foreach ($updated as $object) {
            $objectDAO = new ObjectDAO(self::OBJECT_LABEL, $object->getId(), new \DateTimeImmutable($object->getModifiedTime()->format('r')));

            foreach ($object->dehydrate($mappedFields) as $field => $value) {
                try {
                    $this->getValidator()->validateRequiredField($field, $value);
                    if (!isset($objectFields[$field])) {
                        // If the present value is not described it should be processed as string
                        $normalizedValue = $this->valueNormalizer->normalizeForMautic(NormalizedValueDAO::STRING_TYPE, $value);
                    } else {
                        // Normalize the value from the API to what Mautic needs
                        $normalizedValue = $this->valueNormalizer->normalizeForMautic($objectFields[$field]->getTypeName(), $value);
                    }

                    $reportFieldDAO = new FieldDAO($field, $normalizedValue);

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
     * @param UpdatedObjectMappingDAO[] $objects
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
     * @param Account[] $objects
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
    public function insert(array $objects): array
    {
        return $this->insertInternal($objects, self::OBJECT_NAME);
    }

    /**
     * @throws YellowboxPluginException
     * @throws \Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidQueryArgumentException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     */
    protected function getModel(array $objectData): Account
    {
        $objectFields     = $this->accountRepository->describe()->getFields();
        $normalizedFields = [];

        /**
         * @var string
         * @var FieldDAO $fieldDAO
         */
        foreach ($objectData as $key => $fieldDAO) {
            $normalizedFields[$key] = $this->valueNormalizer->normalizeForYellowbox($objectFields[$fieldDAO->getName()], $fieldDAO);
        }

        return $this->modelFactory->createAccount($normalizedFields);
    }

    protected function getValidator(): AccountValidator
    {
        return $this->accountValidator;
    }

    protected function getRepository(): AccountRepository
    {
        return $this->accountRepository;
    }

    protected function getYellowboxApiQueryLimit(): int
    {
        return self::YELLOWBOX_API_QUERY_LIMIT;
    }
}
