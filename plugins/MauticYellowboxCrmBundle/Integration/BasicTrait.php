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

trait BasicTrait
{
    public function getName(): string
    {
        return YellowboxCrmIntegration::NAME;
    }

    public function getDisplayName(): string
    {
        return 'Yellowbox';
    }
}
