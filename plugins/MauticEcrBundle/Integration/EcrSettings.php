<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEcrBundle\Integration;

use Mautic\CoreBundle\Helper\ArrayHelper;
use Mautic\PluginBundle\Helper\IntegrationHelper;

class EcrSettings
{
    const API_URL = 'https://soutenir.eglisecatholique-ge.ch';
    /**
     * @var array
     */
    private $settings = [];

    /**
     * @var bool|\Mautic\PluginBundle\Integration\AbstractIntegration
     */
    private $integration;

    /**
     * @var IntegrationHelper
     */
    private $integrationHelper;

    /**
     * EcrSettings constructor.
     */
    public function __construct(IntegrationHelper $integrationHelper)
    {
        $this->integrationHelper = $integrationHelper;
    }

    public function initialize()
    {
        if (null === $this->integration) {
            $this->integration = $this->integrationHelper->getIntegrationObject('Ecr');
            if ($this->integration instanceof EcrIntegration && $this->integration->getIntegrationSettings(
                )->getIsPublished()) {
                $this->settings = array_merge(
                    $this->integration->getDecryptedApiKeys(),
                    $this->integration->mergeConfigToFeatureSettings()
                );
            }
        }
    }

    /**
     * @return array
     */
    public function getMatchingFields()
    {
        $this->initialize();

        return ArrayHelper::getValue('matchingFields', $this->settings, []);
    }

    /**
     * @return string|null
     */
    public function getUser()
    {
        $this->initialize();

        return ArrayHelper::getValue('user', $this->settings);
    }

    /**
     * @return string|null
     */
    public function getKey()
    {
        $this->initialize();

        return ArrayHelper::getValue('key', $this->settings);
    }

    /**
     * @return bool
     */
    public function syncDnc()
    {
        $this->initialize();

        return ArrayHelper::getValue('dnc', $this->settings, false);
    }
}
