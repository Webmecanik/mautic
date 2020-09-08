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

namespace MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model;

class Lead extends BaseModel
{
    /**
     * @return string|null
     */
    public function getAssignedUserId()
    {
        return !isset($this->data['ID_GESTIONNAIRE']) ? null : $this->data['ID_GESTIONNAIRE'];
    }

    public function setAssignedUserId(?string $userId): Lead
    {
        $this->data['ID_GESTIONNAIRE'] = $userId;

        return $this;
    }
}