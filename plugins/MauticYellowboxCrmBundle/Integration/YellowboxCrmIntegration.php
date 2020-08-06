<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticYellowboxCrmBundle\Integration;

use Mautic\IntegrationsBundle\Integration\BasicIntegration;
use Mautic\IntegrationsBundle\Integration\Interfaces\BasicInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\IntegrationInterface;

class YellowboxCrmIntegration extends BasicIntegration implements BasicInterface
{
    use BasicTrait;

    const NAME         = 'YellowboxCrm';
    const DISPLAY_NAME = 'Yellowbox CRM';

    public function getIcon(): string
    {
        return 'plugins/MauticYellowboxCrmBundle/Assets/img/icon.jpg';
    }
}
