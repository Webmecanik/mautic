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

/**
 * Class Contact.
 */
class Contact extends BaseModel
{
    public function getAssignedUserId(): ?string
    {
        return $this->data['ID_GESTIONNAIRE'] ?? null;
    }

    public function setAssignedUserId(?string $userId): Contact
    {
        $this->data['ID_GESTIONNAIRE'] = $userId;

        return $this;
    }

    public function isConvertedFromLead(): bool
    {
        return (bool) $this->data['isconvertedfromlead'];
    }

    public function getEmail(): string
    {
        return $this->data['email'];
    }

    public function getEmailOptout(): bool
    {
        return (bool) $this->data['emailoptout'];
    }

    /**
     * @return Contact
     */
    public function setEmailOptout(bool $value): self
    {
        $this->data['emailoptout'] = $value;

        return $this;
    }

    public function getDoNotCall(): bool
    {
        return (bool) $this->data['donotcall'];
    }

    /**
     * @return Contact
     */
    public function setDoNotCall(bool $value): self
    {
        $this->data['donotcall'] = $value;

        return $this;
    }
}
