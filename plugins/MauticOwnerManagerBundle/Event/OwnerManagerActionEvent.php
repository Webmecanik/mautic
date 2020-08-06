<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticOwnerManagerBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\UserBundle\Entity\User;
use MauticPlugin\MauticOwnerManagerBundle\Entity\OwnerManager;

class OwnerManagerActionEvent extends CommonEvent
{
    /**
     * @var OwnerManager
     */
    protected $ownerManager;

    /**
     * @var Lead
     */
    protected $lead;

    public function __construct(OwnerManager &$ownerManager, Lead &$lead)
    {
        $this->ownerManager = $ownerManager;
        $this->lead         = $lead;
    }

    /**
     * Returns the OwnerManager entity.
     *
     * @return OwnerManager
     */
    public function getOwnerManager()
    {
        return $this->ownerManager;
    }

    /**
     * Sets the OwnerManager entity.
     */
    public function setOwnerManager(OwnerManager $ownerManager)
    {
        $this->ownerManager = $ownerManager;
    }

    /**
     * Returns the Lead entity.
     *
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * Sets the Lead entity.
     *
     * @param $lead
     */
    public function setLead($lead)
    {
        $this->lead = $lead;
    }
}
