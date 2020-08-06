<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticOwnerManagerBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use MauticPlugin\MauticOwnerManagerBundle\Entity\OwnerManager;

/**
 * Class OwnerManagerEvent.
 */
class OwnerManagerEvent extends CommonEvent
{
    /**
     * @param bool $isNew
     */
    public function __construct(OwnerManager &$ownerManager, $isNew = false)
    {
        $this->entity = &$ownerManager;
        $this->isNew  = $isNew;
    }

    /**
     * Returns the OwnerManager entity.
     *
     * @return OwnerManager
     */
    public function getOwnerManager()
    {
        return $this->entity;
    }

    /**
     * Sets the OwnerManager entity.
     */
    public function setOwnerManager(OwnerManager $ownerManager)
    {
        $this->entity = $ownerManager;
    }
}
