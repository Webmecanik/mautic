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

namespace MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository\Direction;

use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\ModuleFieldInfo;

class LeadFieldDirection implements FieldDirectionInterface
{
    private $readOnlyFields = [
        'ID_ELEMENT',
        'ID_ELEMENTDEFAUT',
    ];

    private $requiredFields = [
        'EMAIL',
    ];

    /**
     * {@inheritdoc}
     */
    public function isFieldReadable(ModuleFieldInfo $moduleFieldInfo): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isFieldWritable(ModuleFieldInfo $moduleFieldInfo): bool
    {
        return !in_array($moduleFieldInfo->getName(), $this->readOnlyFields, true);
    }

    /**
     * {@inheritdoc}
     */
    public function isFieldRequired(ModuleFieldInfo $moduleFieldInfo): bool
    {
        return in_array($moduleFieldInfo->getNormalizeName(), $this->requiredFields, true);
    }
}
