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

use Mautic\CampaignBundle\Event\AbstractLogCollectionEvent;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticOwnerManagerBundle\Entity\OwnerManager;

class OwnerManagerChangeActionExecutedEvent extends AbstractLogCollectionEvent
{
    /**
     * @var OwnerManager
     */
    private $ownerManager;

    /**
     * @var Lead
     */
    private $lead;

    /**
     * @var
     */
    private $eventDetails;

    /**
     * @var bool
     */
    private $result;

    /**
     * @var array
     */
    private $completedActions;

    /**
     * OwnerManagerChangeActionExecutedEvent constructor.
     *
     * @param mixed $eventDetails
     * @param array $completedActions
     */
    public function __construct(OwnerManager $ownerManager, Lead $lead, $eventDetails, $completedActions = [])
    {
        $this->ownerManager     = $ownerManager;
        $this->lead             = $lead;
        $this->eventDetails     = $eventDetails;
        $this->completedActions = $completedActions;
    }

    /**
     * @return bool
     */
    public function changeOwner()
    {
        return $this->result;
    }

    public function setSucceded()
    {
        $this->result = true;
    }

    /**
     * @return bool
     */
    public function setSucceedIfNotAlreadyTriggered()
    {
        $this->result = !(isset($this->completedActions[$this->ownerManager->getId()]));
    }

    /**
     * @param $internalId
     *
     * @return bool
     */
    public function setSucceedIfNotAlreadyTriggeredByInternalId($internalId)
    {
        $this->result = !(in_array($internalId, array_column($this->completedActions, 'internal_id')));
    }

    /**
     * @return OwnerManager
     */
    public function getOwnerManager()
    {
        return $this->ownerManager;
    }

    /**
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @return mixed
     */
    public function getEventDetails()
    {
        return $this->eventDetails;
    }

    /**
     * @return array
     */
    public function getCompletedActions()
    {
        return $this->completedActions;
    }

    /**
     * @param string $context
     *
     * @return bool
     */
    public function checkContext($context)
    {
        return $this->getOwnerManager()->getType() === $context;
    }
}
