<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDivaltoBundle\Integration;

use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Exception\InvalidValueException;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use Mautic\IntegrationsBundle\Sync\Logger\DebugLogger;
use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\MauticDivaltoBundle\Sync\Mapping\Manual\MappingManualFactory;
use Psr\Log\LogLevel;

class Config
{
    /**
     * @var IntegrationsHelper
     */
    private $integrationsHelper;

    /**
     * @var array[]
     */
    private $fieldDirections = [];

    /**
     * @var array[]
     */
    private $mappedFields = [];

    /**
     * Config constructor.
     */
    public function __construct(IntegrationsHelper $integrationsHelper)
    {
        $this->integrationsHelper = $integrationsHelper;
    }

    public function isPublished(): bool
    {
        try {
            $integration = $this->getIntegrationEntity();

            return (bool) $integration->getIsPublished() ?: false;
        } catch (IntegrationNotFoundException $e) {
            return false;
        }
    }

    public function isConfigured(): bool
    {
        $apiKeys = $this->getApiKeys();

        return !empty($apiKeys['projectCode']) && !empty($apiKeys['username']) && !empty($apiKeys['password']);
    }

    public function isAuthorized(): bool
    {
        $apiKeys = $this->getApiKeys();

        return !empty($apiKeys['refresh_token']);
    }

    /**
     * @return mixed[]
     */
    public function getFeatureSettings(): array
    {
        try {
            $integration = $this->getIntegrationEntity();

            return $integration->getFeatureSettings() ?: [];
        } catch (IntegrationNotFoundException $e) {
            return [];
        }
    }

    public function syncContactsCompany(): bool
    {
        return array_key_exists('syncContactsCompany', array_flip($this->getFeatureSettings()['integration']['settings']));
    }

    public function enabledObject(string $objectName): bool
    {
        return array_key_exists($objectName, array_flip($this->getFeatureSettings()['sync']['objects']));
    }

    /**
     * @param string $message
     */
    public function log($message)
    {
        $logLevel = LogLevel::DEBUG;
        if (defined('MAUTIC_ENV') && MAUTIC_ENV === 'prod' && array_key_exists('debugMode', array_flip($this->getFeatureSettings()['integration']['settings']))) {
            $logLevel = LogLevel::WARNING;
        }
        DebugLogger::log(DivaltoIntegration::DISPLAY_NAME, $message, null, [], $logLevel);
    }

    /**
     * @return string[]
     */
    public function getApiKeys(): array
    {
        try {
            $integration = $this->getIntegrationEntity();

            return $integration->getApiKeys() ?: [];
        } catch (IntegrationNotFoundException $e) {
            return [];
        }
    }

    /**
     * @throws InvalidValueException
     */
    public function getFieldDirection(string $objectName, string $alias): string
    {
        if (isset($this->getMappedFieldsDirections($objectName)[$alias])) {
            return $this->getMappedFieldsDirections($objectName)[$alias];
        }

        throw new InvalidValueException("There is no field direction for '{$objectName}' field '${alias}'.");
    }

    /**
     * Returns mapped fields that the user configured for this integration in the format of [field_alias => mautic_field_alias].
     *
     * @return string[]
     */
    public function getMappedFields(string $objectName): array
    {
        if (isset($this->mappedFields[$objectName])) {
            return $this->mappedFields[$objectName];
        }

        $fieldMappings = $this->getFeatureSettings()['sync']['fieldMappings'][$objectName] ?? [];

        $this->mappedFields[$objectName] = [];
        foreach ($fieldMappings as $field => $fieldMapping) {
            $this->mappedFields[$objectName][$field] = $fieldMapping['mappedField'];
        }

        if (MappingManualFactory::CONTACT_OBJECT === $objectName && $this->syncContactsCompany()) {
        }

        return $this->mappedFields[$objectName];
    }

    /**
     * @throws IntegrationNotFoundException
     */
    public function getIntegrationEntity(): Integration
    {
        $integrationObject = $this->integrationsHelper->getIntegration(DivaltoIntegration::NAME);

        return $integrationObject->getIntegrationConfiguration();
    }

    /**
     * Returns direction of what field to sync where in the format of [field_alias => direction].
     *
     * @return string[]
     */
    private function getMappedFieldsDirections(string $objectName): array
    {
        if (isset($this->fieldDirections[$objectName])) {
            return $this->fieldDirections[$objectName];
        }

        $fieldMappings = $this->getFeatureSettings()['sync']['fieldMappings'][$objectName] ?? [];

        $this->fieldDirections[$objectName] = [];
        foreach ($fieldMappings as $field => $fieldMapping) {
            $this->fieldDirections[$objectName][$field] = $fieldMapping['syncDirection'];
        }

        return $this->fieldDirections[$objectName];
    }
}
