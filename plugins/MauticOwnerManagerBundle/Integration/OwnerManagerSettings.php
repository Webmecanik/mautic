<?php

/*
 * @copyright   2019 MTCExtendee. All rights reserved
 * @author      MTCExtendee
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticOwnerManagerBundle\Integration;

use Mautic\CoreBundle\Helper\ArrayHelper;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use MauticPlugin\MauticOwnerManagerBundle\Entity\OwnerManager;

class OwnerManagerSettings
{
    /**
     * @var bool|\Mautic\PluginBundle\Integration\AbstractIntegration
     */
    private $integration;

    private $enabled = false;

    /**
     * DolistSettings constructor.
     */
    public function __construct(IntegrationHelper $integrationHelper)
    {
        $this->integration = $integrationHelper->getIntegrationObject(OwnerManagerIntegration::INTEGRATION_NAME);
        if ($this->integration instanceof OwnerManagerIntegration && $this->integration->getIntegrationSettings(
            )->getIsPublished()) {
            $this->enabled = true;
        }
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }
}
