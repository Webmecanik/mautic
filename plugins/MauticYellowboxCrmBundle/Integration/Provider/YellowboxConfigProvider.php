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

namespace MauticPlugin\MauticYellowboxCrmBundle\Integration\Provider;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\IntegrationsBundle\Integration\ConfigurationTrait;
use Mautic\IntegrationsBundle\Integration\DefaultConfigFormTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormAuthInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormFeaturesInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormSyncInterface;
use Mautic\IntegrationsBundle\Mapping\MappedFieldInfoInterface;
use MauticPlugin\MauticYellowboxCrmBundle\Form\Type\ConfigAuthType;
use MauticPlugin\MauticYellowboxCrmBundle\Form\Type\ConfigSyncFeaturesType;
use MauticPlugin\MauticYellowboxCrmBundle\Integration\BasicTrait;
use MauticPlugin\MauticYellowboxCrmBundle\Mapping\ObjectFieldMapper;

class YellowboxConfigProvider implements ConfigFormInterface, ConfigFormSyncInterface, ConfigFormAuthInterface, ConfigFormFeaturesInterface
{
    use BasicTrait;
    use ConfigurationTrait;
    use DefaultConfigFormTrait;

    /**
     * @var ObjectFieldMapper
     */
    private $fieldMapping;

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * YellowboxConfigProvider constructor.
     */
    public function __construct(ObjectFieldMapper $fieldMapping, CoreParametersHelper $coreParametersHelper)
    {
        $this->fieldMapping         = $fieldMapping;
        $this->coreParametersHelper = $coreParametersHelper;
    }

    public function getAuthConfigFormName(): string
    {
        return ConfigAuthType::class;
    }

    public function getSyncConfigFormName(): ?string
    {
        return ConfigSyncFeaturesType::class;
    }

    public function getSupportedFeatures(): array
    {
        return [
            ConfigFormFeaturesInterface::FEATURE_SYNC          => 'mautic.integration.feature.sync',
            //ConfigFormFeaturesInterface::FEATURE_PUSH_ACTIVITY => 'mautic.integration.feature.push_activity',
        ];
    }

    /**
     * @return array|MappedFieldInfoInterface[]
     *
     * @throws \Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidQueryArgumentException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\YellowboxPluginException
     */
    public function getOptionalFieldsForMapping(string $object): array
    {
        $fields         = $this->getFields($object);
        $optionalFields = [];
        foreach ($fields as $fieldName => $field) {
            if ($field->showAsRequired()) {
                continue;
            }

            $optionalFields[$fieldName] = $field;
        }

        return $optionalFields;
    }

    /**
     * @return array|MappedFieldInfoInterface[]
     *
     * @throws \Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidQueryArgumentException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\YellowboxPluginException
     */
    public function getRequiredFieldsForMapping(string $object): array
    {
        $fields = $this->getFields($object);

        $requiredFields = [];
        foreach ($fields as $fieldName => $field) {
            if (!$field->showAsRequired()) {
                continue;
            }

            $requiredFields[$fieldName] = $field;
        }

        return $requiredFields;
    }

    /**
     * @return array|MappedFieldInfoInterface[]
     *
     * @throws \Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidQueryArgumentException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\YellowboxPluginException
     */
    public function getAllFieldsForMapping(string $object): array
    {
        $requiredFields = $this->getRequiredFieldsForMapping($object);
        asort($requiredFields);

        $optionalFields = $this->getOptionalFieldsForMapping($object);
        asort($optionalFields);

        return array_merge($requiredFields, $optionalFields);
    }

    public function getSyncConfigObjects(): array
    {
        $objects = [
            'Leads'    => 'mautic.plugin.yellowbox.object.lead',
            'Contacts' => 'mautic.plugin.yellowbox.object.contact',
            'Accounts' => 'mautic.plugin.yellowbox.object.company',
        ];

        $yellowboxAvailableObjects = $this->coreParametersHelper->getParameter('yellowboxAvailableObjects');

        return array_filter(
            $objects,
            function ($value, $key) use ($yellowboxAvailableObjects) {
                return in_array($key, $yellowboxAvailableObjects);
            },
            ARRAY_FILTER_USE_BOTH
        );
    }

    public function getSyncMappedObjects(): array
    {
        return $this->fieldMapping->getMapping();
    }

    /**
     * @return array|MappedFieldInfoInterface[]
     *
     * @throws \Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidQueryArgumentException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\YellowboxPluginException
     */
    private function getFields(string $object): array
    {
        $fields = $this->fieldMapping->getObjectFields($object);
        unset($fields['ID_GESTIONNAIRE']);

        return $fields;
    }
}
