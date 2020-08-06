<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticOwnerManagerBundle\Model;

use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\FormModel as CommonFormModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\MauticOwnerManagerBundle\Entity\Action;
use MauticPlugin\MauticOwnerManagerBundle\Entity\OwnerManager;
use MauticPlugin\MauticOwnerManagerBundle\Entity\OwnerManagerLog;
use MauticPlugin\MauticOwnerManagerBundle\Entity\Point;
use MauticPlugin\MauticOwnerManagerBundle\Event\OwnerManagerActionEvent;
use MauticPlugin\MauticOwnerManagerBundle\Event\OwnerManagerBuilderEvent;
use MauticPlugin\MauticOwnerManagerBundle\Event\OwnerManagerChangeActionExecutedEvent;
use MauticPlugin\MauticOwnerManagerBundle\Event\OwnerManagerEvent;
use MauticPlugin\MauticOwnerManagerBundle\Event\PointEvent;
use MauticPlugin\MauticOwnerManagerBundle\Form\Type\OwnerManagerType;
use MauticPlugin\MauticOwnerManagerBundle\Integration\OwnerManagerSettings;
use MauticPlugin\MauticOwnerManagerBundle\OwnerManagerEvents;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class OwnerManagerModel extends CommonFormModel
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * @var IpLookupHelper
     */
    protected $ipLookupHelper;

    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    /**
     * @var OwnerManagerSettings
     */
    private $ownerManagerSettings;

    /**
     * PointModel constructor.
     */
    public function __construct(Session $session, IpLookupHelper $ipLookupHelper, LeadModel $leadModel, OwnerManagerSettings $ownerManagerSettings)
    {
        $this->session              = $session;
        $this->ipLookupHelper       = $ipLookupHelper;
        $this->leadModel            = $leadModel;
        $this->propertyAccessor     = new PropertyAccessor();
        $this->ownerManagerSettings = $ownerManagerSettings;
    }

    /**
     * {@inheritdoc}
     *
     * @return \MauticPlugin\MauticOwnerManagerBundle\Entity\OwnerManagerRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticOwnerManagerBundle:OwnerManager');
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissionBase()
    {
        return 'ownermanager:ownermanager';
    }

    /**
     * {@inheritdoc}
     *
     * @throws MethodNotAllowedHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = [])
    {
        if (!$entity instanceof OwnerManager) {
            throw new MethodNotAllowedHttpException(['OwnerManager']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        if (empty($options['ownermanagerActions'])) {
            $options['ownermanagerActions'] = $this->getOwnerManagerActions();
        }

        return $formFactory->create(OwnerManagerType::class, $entity, $options);
    }

    /**
     * {@inheritdoc}
     *
     * @return OwnerManager|object|null
     */
    public function getEntity($id = null)
    {
        if (null === $id) {
            return new OwnerManager();
        }

        return parent::getEntity($id);
    }

    /**
     * {@inheritdoc}
     *
     * @throws MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null)
    {
        if (!$entity instanceof OwnerManager) {
            throw new MethodNotAllowedHttpException(['OwnerManager']);
        }

        switch ($action) {
            case 'pre_save':
                $name = OwnerManagerEvents::OWNER_MANAGER_PRE_SAVE;
                break;
            case 'post_save':
                $name = OwnerManagerEvents::OWNER_MANAGER_POST_SAVE;
                break;
            case 'pre_delete':
                $name = OwnerManagerEvents::OWNER_MANAGER_PRE_DELETE;
                break;
            case 'post_delete':
                $name = OwnerManagerEvents::OWNER_MANAGER_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new OwnerManagerEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($name, $event);

            return $event;
        }

        return null;
    }

    /**
     * Gets array of custom actions from bundles subscribed PointEvents::POINT_ON_BUILD.
     *
     * @return mixed
     */
    public function getOwnerManagerActions()
    {
        static $actions;

        if (empty($actions)) {
            //build them
            $actions = [];
            $event   = new OwnerManagerBuilderEvent($this->translator);
            $this->dispatcher->dispatch(OwnerManagerEvents::OWNERMANAGER_ON_BUILD, $event);

            $actions['actions'] = $event->getActions();
            $actions['list']    = $event->getActionList();
            $actions['choices'] = $event->getActionChoices();
        }

        return $actions;
    }

    /***
     * @param           $type
     * @param null      $eventDetails
     * @param Lead|null $lead
     *
     * @throws \Exception
     */
    public function triggerAction($type, $eventDetails = null, Lead $lead, $internalId = null)
    {
        // Skip If integration disabled
        if (!$this->ownerManagerSettings->isEnabled()) {
            return;
        }

        /** @var \MauticPlugin\MauticOwnerManagerBundle\Entity\OwnerManagerRepository $repo */
        $repo              = $this->getRepository();
        $availableEntities = $repo->getPublishedByType($type);
        $ipAddress         = $this->ipLookupHelper->getIpAddress();

        // If not exists  internalID, try catch from getId
        if (null === $internalId) {
            try {
                $internalId = $this->propertyAccessor->getValue($eventDetails, 'id');
            } catch (NoSuchPropertyException $e) {
            }
        }

        //get available actions
        $availableActions = $this->getOwnerManagerActions();
        //get a list of actions that has already been performed on this lead
        $completedActions = $repo->getCompletedLeadActions($type, $lead->getId());
        $persist          = [];
        /** @var OwnerManager $action */
        foreach ($availableEntities as $action) {
            //make sure the action still exists
            if (!isset($availableActions['actions'][$action->getType()])) {
                continue;
            }
            $settings                       = $availableActions['actions'][$action->getType()];
            $pointChangeActionExecutedEvent = new OwnerManagerChangeActionExecutedEvent(
                $action,
                $lead,
                $eventDetails,
                $completedActions
            );
            $event                          = $this->dispatcher->dispatch(
                $settings['eventName'],
                $pointChangeActionExecutedEvent
            );

            if (!$event->changeOwner()) {
                continue;
            }

            $lead->setOwner($action->getOwner());

            $event = new OwnerManagerActionEvent($action, $lead);
            $this->dispatcher->dispatch(OwnerManagerEvents::OWNERMANAGER_ON_ACTION, $event);

            $log = new OwnerManagerLog();
            $log->setIpAddress($ipAddress);
            $log->setOwnerManager($action);
            $log->setOwner($action->getOwner());
            $log->setLead($lead);
            $log->setInternalId($internalId);
            $log->setDateFired(new \DateTime());
            $persist[] = $log;
        }

        if (!empty($persist)) {
            $this->getRepository()->saveEntities($persist);
            // Detach logs to reserve memory
            $this->em->clear(OwnerManagerLog::class);
        }
        if (!empty($lead->getChanges())) {
            $this->leadModel->saveEntity($lead);
        }
    }
}
