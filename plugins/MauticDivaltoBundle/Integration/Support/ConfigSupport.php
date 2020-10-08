<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDivaltoBundle\Integration\Support;

use Mautic\IntegrationsBundle\Integration\DefaultConfigFormTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormAuthInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormFeatureSettingsInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormFeaturesInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormSyncInterface;
use Mautic\IntegrationsBundle\Mapping\MappedFieldInfoInterface;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Company;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use MauticPlugin\MauticDivaltoBundle\Form\Type\ConfigAuthType;
use MauticPlugin\MauticDivaltoBundle\Form\Type\ConfigFeaturesType;
use MauticPlugin\MauticDivaltoBundle\Integration\DivaltoIntegration;
use MauticPlugin\MauticDivaltoBundle\Sync\Mapping\Field\FieldRepository;
use MauticPlugin\MauticDivaltoBundle\Sync\Mapping\Manual\MappingManualFactory;

class ConfigSupport extends DivaltoIntegration implements ConfigFormInterface, ConfigFormAuthInterface, ConfigFormFeatureSettingsInterface, ConfigFormSyncInterface, ConfigFormFeaturesInterface
{
    use DefaultConfigFormTrait;

    /**
     * @var FieldRepository
     */
    private $fieldRepository;

    /**
     * ConfigSupport constructor.
     */
    public function __construct(FieldRepository $fieldRepository)
    {
        $this->fieldRepository = $fieldRepository;
    }

    public function getAuthConfigFormName(): string
    {
        return ConfigAuthType::class;
    }

    public function getFeatureSettingsConfigFormName(): string
    {
        return ConfigFeaturesType::class;
    }

    public function getSyncConfigObjects(): array
    {
        return [
            MappingManualFactory::COMPANY_OBJECT => 'divalto.object.company',
            MappingManualFactory::CONTACT_OBJECT => 'divalto.object.contact',
            MappingManualFactory::LEAD_OBJECT    => 'divalto.object.marketing_contact'];
    }

    public function getSyncMappedObjects(): array
    {
        return [
            MappingManualFactory::LEAD_OBJECT      => Contact::NAME,
            MappingManualFactory::CONTACT_OBJECT   => Contact::NAME,
            MappingManualFactory::COMPANY_OBJECT   => Company::NAME];
    }

    /**
     * @return MappedFieldInfoInterface[]
     */
    public function getRequiredFieldsForMapping(string $objectName): array
    {
        return $this->fieldRepository->getRequiredFieldsForMapping($objectName);
    }

    /**
     * @return MappedFieldInfoInterface[]
     */
    public function getOptionalFieldsForMapping(string $objectName): array
    {
        $this->fieldRepository->getOptionalFieldsForMapping($objectName);
    }

    /**
     * @return MappedFieldInfoInterface[]
     */
    public function getAllFieldsForMapping(string $objectName): array
    {
        // Order fields by required alphabetical then optional alphabetical
        $sorter = function (MappedFieldInfoInterface $field1, MappedFieldInfoInterface $field2) {
            return strnatcasecmp($field1->getLabel(), $field2->getLabel());
        };

        $requiredFields = $this->fieldRepository->getRequiredFieldsForMapping($objectName);
        uasort($requiredFields, $sorter);

        $optionalFields = $this->fieldRepository->getOptionalFieldsForMapping($objectName);
        uasort($optionalFields, $sorter);

        return array_merge($requiredFields,
            $optionalFields);
    }

    public function getSupportedFeatures(): array
    {
        return [
            ConfigFormFeaturesInterface::FEATURE_SYNC => 'mautic.integration.feature.sync'];
    }
}
