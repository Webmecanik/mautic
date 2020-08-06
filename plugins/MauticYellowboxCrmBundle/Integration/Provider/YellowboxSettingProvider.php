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

use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\MauticYellowboxCrmBundle\Enum\SettingsKeyEnum;
use MauticPlugin\MauticYellowboxCrmBundle\Integration\Provider\DTO\AddressSettings;
use MauticPlugin\MauticYellowboxCrmBundle\Integration\YellowboxCrmIntegration;

class YellowboxSettingProvider
{
    /**
     * @var IntegrationsHelper
     */
    private $integrationsHelper;

    /**
     * @var Integration
     */
    private $integrationEntity;

    public function __construct(IntegrationsHelper $helper)
    {
        $this->integrationsHelper = $helper;
    }

    public function getCredentials(): array
    {
        if (null === $this->getIntegrationEntity()) {
            return [];
        }

        return $this->integrationEntity->getApiKeys();
    }

    public function isConfigured(): bool
    {
        $credentialsCfg = $this->getCredentials();

        return !((!isset($credentialsCfg['password']) || !isset($credentialsCfg['username']) || !isset($credentialsCfg['url'])));
    }

    /**
     * @throws PluginNotConfiguredException
     */
    public function exceptConfigured(): void
    {
        if (!$this->isConfigured()) {
            throw new PluginNotConfiguredException(YellowboxCrmIntegration::NAME.' is not configured');
        }
    }

    public function getSyncObjects(): array
    {
        return $this->getSettings()['sync']['objects'] ?? [];
    }

    public function getFieldMappings(string $object): array
    {
        return $this->getSettings()['sync']['fieldMappings'][$object] ?? [];
    }

    /**
     * @throws PluginNotConfiguredException
     */
    public function isActivitySyncEnabled(): bool
    {
        $this->exceptConfigured();

        return false;
    }

    public function getActivityEvents(): array
    {
        return [];
    }

    public function isOwnerUpdateEnabled(): bool
    {
        return (bool) $this->getSyncSetting(SettingsKeyEnum::OWNER_UPDATE_IS_ENABLED);
    }

    public function getOwner(): string
    {
        return (string) $this->getSyncSetting(SettingsKeyEnum::OWNER);
    }

    public function isMauticOwnerUpdateEnabled(): bool
    {
        return (bool) $this->getSyncSetting(SettingsKeyEnum::OWNER_MAUTIC_UPDATE_IS_ENABLED);
    }

    public function getMauticOwnerUserRole(): int
    {
        return $this->getSyncSetting(SettingsKeyEnum::OWNER_MAUTIC_UPDATE_USER_ROLE);
    }

    public function shouldBeMauticContactPushedAsLead(): bool
    {
        return SettingsKeyEnum::PUSH_MAUTIC_CONTACT_AS_LEAD === $this->getSyncSetting(SettingsKeyEnum::PUSH_MAUTIC_CONTACT_AS);
    }

    public function shouldBeMauticContactPushedAsContact(): bool
    {
        return SettingsKeyEnum::PUSH_MAUTIC_CONTACT_AS_CONTACT === $this->getSyncSetting(SettingsKeyEnum::PUSH_MAUTIC_CONTACT_AS);
    }

    /**
     * @return AddressSettings
     */
    public function getAddressSettings()
    {
        return new AddressSettings($this->getSettings());
    }

    /**
     * Gets a setting from the ConfigSyncFeaturesType form.
     *
     * @return mixed
     */
    private function getSyncSetting(string $settingName)
    {
        $settings = $this->getSettings()['sync']['integration'] ?? [];
        if (!array_key_exists($settingName, $settings)) {
            throw new \InvalidArgumentException(sprintf('Setting "%s" does not exists, supported: %s', $settingName, join(', ', array_keys($settings))));
        }

        return $settings[$settingName];
    }

    private function getSettings(): array
    {
        if (null === $this->getIntegrationEntity()) {
            return [];
        }

        return $this->integrationEntity->getFeatureSettings();
    }

    private function getIntegrationEntity(): ?Integration
    {
        if (is_null($this->integrationEntity)) {
            try {
                $integrationObject       = $this->integrationsHelper->getIntegration(YellowboxCrmIntegration::NAME);
                $this->integrationEntity = $integrationObject->getIntegrationConfiguration();
                if (!$this->integrationEntity->getIsPublished()) {
                    return null;
                }
            } catch (IntegrationNotFoundException $exception) {
                return null;
            }
        }

        return $this->integrationEntity;
    }
}
