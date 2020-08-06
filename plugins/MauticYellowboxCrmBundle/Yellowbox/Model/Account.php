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

class Account extends BaseModel
{
    public function getAssignedUserId(): ?string
    {
        return !isset($this->data['ID_GESTIONNAIRE']) ? null : $this->data['ID_GESTIONNAIRE'];
    }

    /** @noinspection PhpDocSignatureInspection */

    /**
     * @return Contact
     */
    public function setAssignedUserId(?string $userId): Account
    {
        $this->data['ID_GESTIONNAIRE'] = $userId;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getAccountName();
    }
}
