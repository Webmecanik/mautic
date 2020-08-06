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
     * EcrSettings constructor.
     */
    public function __construct(IntegrationHelper $integrationHelper)
    {
        $this->integration = $integrationHelper->getIntegrationObject('Ecr');
        if ($this->integration instanceof EcrIntegration && $this->integration->getIntegrationSettings()->getIsPublished()) {
            $this->settings = array_merge(
                $this->integration->getDecryptedApiKeys(),
                $this->integration->mergeConfigToFeatureSettings()
            );
        }
    }

    /**
     * @return array
     */
    public function getMatchingFields()
    {
        return ArrayHelper::getValue('matchingFields', $this->settings, []);
    }

    /**
     * @return string|null
     */
    public function getUser()
    {
        return ArrayHelper::getValue('user', $this->settings);
    }

    /**
     * @return string|null
     */
    public function getKey()
    {
        return ArrayHelper::getValue('key', $this->settings);
    }

    /**
     * @return bool
     */
    public function syncDnc()
    {
        return ArrayHelper::getValue('dnc', $this->settings, false);
    }
}
