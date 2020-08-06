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

namespace MauticPlugin\MauticYellowboxCrmBundle\Mapping;

use Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException;
use Mautic\IntegrationsBundle\Mapping\MappedFieldInfoInterface;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\ObjectMappingDAO;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectNotSupportedException;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidQueryArgumentException;
use MauticPlugin\MauticYellowboxCrmBundle\Integration\Provider\YellowboxSettingProvider;
use MauticPlugin\MauticYellowboxCrmBundle\Integration\YellowboxCrmIntegration;
use MauticPlugin\MauticYellowboxCrmBundle\Sync\ContactDataExchange;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository\AccountRepository;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository\ContactRepository;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository\LeadRepository;

/**
 * Class ObjectFieldMapper provides all necessary information  to supply mapping information.
 */
class ObjectFieldMapper
{
    /**
     * Map Mautic objects to Yellowbox module objects.
     *
     * @var array
     */
    private $yellowbox2mauticObjectMapping = [
        'Contacts' => MauticSyncDataExchange::OBJECT_CONTACT,
        'Leads'    => MauticSyncDataExchange::OBJECT_CONTACT,
        'Accounts' => MauticSyncDataExchange::OBJECT_COMPANY,
    ];

    /**
     * @var YellowboxSettingProvider
     */
    private $settingProvider;

    /**
     * @var ContactRepository
     */
    private $contactRepository;

    /**
     * @var LeadRepository
     */
    private $leadRepository;

    /**
     * @var AccountRepository
     */
    private $accountRepository;

    public function __construct(
        YellowboxSettingProvider $settingProvider,
        ContactRepository $contactRepository,
        LeadRepository $leadRepository,
        AccountRepository $accountRepository
    ) {
        $this->settingProvider   = $settingProvider;
        $this->contactRepository = $contactRepository;
        $this->leadRepository    = $leadRepository;
        $this->accountRepository = $accountRepository;
    }

    /**
     * @return array|MappedFieldInfoInterface[]
     *
     * @throws InvalidQueryArgumentException
     * @throws PluginNotConfiguredException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\YellowboxPluginException
     */
    public function getObjectFields(string $objectName): array
    {
        switch ($objectName) {
            case 'Contacts':
                $fields = $this->contactRepository->getMappableFields();
                break;
            case 'Leads':
                $fields = $this->leadRepository->getMappableFields();
                break;
            case 'Accounts':
                $fields = $this->accountRepository->getMappableFields();
                break;
            default:
                throw new InvalidQueryArgumentException('Unknown object '.$objectName);
        }

        return $fields;
    }

    /**
     * @throws InvalidQueryArgumentException
     * @throws ObjectNotSupportedException
     * @throws PluginNotConfiguredException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\YellowboxPluginException
     */
    public function getObjectsMappingManual(): MappingManualDAO
    {
        $mappingManual = new MappingManualDAO(YellowboxCrmIntegration::NAME);

        foreach ($this->settingProvider->getSyncObjects() as $yellowboxObject) {
            $objectMapping = new ObjectMappingDAO(
                $this->getYellowbox2MauticObjectNameMapping($yellowboxObject),
                $yellowboxObject
            );

            try {
                $availableFields = $this->getObjectFields($yellowboxObject);
            } catch (PluginNotConfiguredException $exception) {
                continue;
            }

            foreach ($this->settingProvider->getFieldMappings($yellowboxObject) as $yellowboxField => $fieldMapping) {
                if (!isset($availableFields[$yellowboxField])) {
                    continue;
                }
                $objectMapping->addFieldMapping(
                    $fieldMapping['mappedField'],
                    $yellowboxField,
                    $fieldMapping['syncDirection'],
                    $availableFields[$yellowboxField]->showAsRequired()
                );
            }
            if (ContactDataExchange::OBJECT_LABEL === $yellowboxObject && $this->settingProvider->isMauticOwnerUpdateEnabled()) {
                $objectMapping->addFieldMapping('owner_id', 'ID_GESTIONNAIRE', ObjectMappingDAO::SYNC_TO_MAUTIC, false);
            }

            $mappingManual->addObjectMapping($objectMapping);
        }

        return $mappingManual;
    }

    /**
     * @param $objectName
     *
     * @throws ObjectNotSupportedException
     */
    public function getMautic2YellowboxObjectNameMapping($objectName): string
    {
        if (false === ($key = array_search($objectName, $this->yellowbox2mauticObjectMapping))) {
            throw new ObjectNotSupportedException('Mautic', $objectName);
        }

        return $key;
    }

    /**
     * @param $yellowboxObjectName
     *
     * @return mixed
     *
     * @throws ObjectNotSupportedException
     */
    public function getYellowbox2MauticObjectNameMapping($yellowboxObjectName)
    {
        if (!isset($this->yellowbox2mauticObjectMapping[$yellowboxObjectName])) {
            throw new ObjectNotSupportedException(YellowboxCrmIntegration::NAME, $yellowboxObjectName);
        }

        return $this->yellowbox2mauticObjectMapping[$yellowboxObjectName];
    }

    public function getMapping(): array
    {
        return $this->yellowbox2mauticObjectMapping;
    }
}
