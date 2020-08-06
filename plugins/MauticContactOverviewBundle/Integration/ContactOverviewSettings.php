<?php

/*
 * @copyright   2019 MTCExtendee. All rights reserved
 * @author      MTCExtendee
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactOverviewBundle\Integration;

use Mautic\CoreBundle\Helper\ArrayHelper;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\CoreBundle\Helper\HashHelper\HashHelper;
use Mautic\PluginBundle\Helper\IntegrationHelper;

class ContactOverviewSettings
{
    const contactOverviewToken      = '{contact_overview_url}';

    /**
     * @var bool|\Mautic\PluginBundle\Integration\AbstractIntegration
     */
    private $integration;

    private $enabled = false;

    /**
     * @var array
     */
    private $settings;

    /**
     * @var EncryptionHelper
     */
    private $encryptionHelper;

    /**
     * DolistSettings constructor.
     */
    public function __construct(IntegrationHelper $integrationHelper, EncryptionHelper $encryptionHelper)
    {
        $this->integration = $integrationHelper->getIntegrationObject('ContactOverview');
        if ($this->integration instanceof ContactOverviewIntegration && $this->integration->getIntegrationSettings(
            )->getIsPublished()) {
            $this->enabled  = true;
            $this->settings = $this->integration->mergeConfigToFeatureSettings();
        }
        $this->encryptionHelper = $encryptionHelper;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @return array
     */
    public function getEvents()
    {
        return ArrayHelper::getValue('events', $this->settings, []);
    }

    /**
     * @return EncryptionHelper
     */
    public function encryption()
    {
        return $this->encryptionHelper;
    }
}
