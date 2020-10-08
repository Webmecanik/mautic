<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDivaltoBundle\Sync\Mapping\Manual;

use Mautic\IntegrationsBundle\Exception\InvalidValueException;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\ObjectMappingDAO;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Company;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use MauticPlugin\MauticDivaltoBundle\Integration\Config;
use MauticPlugin\MauticDivaltoBundle\Integration\DivaltoIntegration;
use MauticPlugin\MauticDivaltoBundle\Sync\Mapping\Field\Field;
use MauticPlugin\MauticDivaltoBundle\Sync\Mapping\Field\FieldRepository;

class MappingManualFactory
{
    public const LEAD_OBJECT      = 'marketinginbound';
    public const CONTACT_OBJECT   = 'customercontact';
    public const COMPANY_OBJECT   = 'customer';

    /**
     * @var FieldRepository
     */
    private $fieldRepository;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var MappingManualDAO
     */
    private $manual;

    public function __construct(FieldRepository $fieldRepository, Config $config)
    {
        $this->fieldRepository = $fieldRepository;
        $this->config          = $config;
    }

    public function getManual(): MappingManualDAO
    {
        if ($this->manual) {
            return $this->manual;
        }

        // Instructions to the sync engine on how to map fields and the direction of data should flow
        $this->manual = new MappingManualDAO(DivaltoIntegration::NAME);

        // In this case, two objects are supported. Contact to Mautic Contact and Company to Mautic Company.
        $this->configureObjectMapping(self::LEAD_OBJECT);
        $this->configureObjectMapping(self::CONTACT_OBJECT);
        $this->configureObjectMapping(self::COMPANY_OBJECT);

        return $this->manual;
    }

    private function configureObjectMapping(string $objectName): void
    {
        // Get a list of available fields from the integration
        $fields = $this->fieldRepository->getFields($objectName);

        // Get a list of fields mapped by the user
        $mappedFields = $this->config->getMappedFields($objectName);

        // Generate an object mapping DAO for the given object. The object must be mapped to a supported Mautic object (i.e. contact or company)
        $objectMappingDAO = new ObjectMappingDAO($this->getMauticObjectName($objectName), $objectName);

        foreach ($mappedFields as $fieldAlias => $mauticFieldAlias) {
            if (!isset($fields[$fieldAlias])) {
                // The mapped field is no longer available
                continue;
            }

            /** @var Field $field */
            $field = $fields[$fieldAlias];

            // Configure how fields should be handled by the sync engine as determined by the user's configuration.
            $objectMappingDAO->addFieldMapping(
                $mauticFieldAlias,
                $fieldAlias,
                $this->config->getFieldDirection($objectName, $fieldAlias),
                $field->isRequired()
            );
        }

        if (MappingManualFactory::CONTACT_OBJECT === $objectName && $this->config->syncContactsCompany()) {
            $objectMappingDAO->addFieldMapping('company_id', 'customer_ID', ObjectMappingDAO::SYNC_TO_MAUTIC, false);
        }

        $this->manual->addObjectMapping($objectMappingDAO);
    }

    /**
     * @throws InvalidValueException
     */
    private function getMauticObjectName(string $objectName): string
    {
        switch ($objectName) {
            case self::COMPANY_OBJECT:
                return Company::NAME;
            case self::LEAD_OBJECT:
            case self::CONTACT_OBJECT:
                return Contact::NAME;
        }

        throw new InvalidValueException("$objectName could not be mapped to a Mautic object");
    }
}
