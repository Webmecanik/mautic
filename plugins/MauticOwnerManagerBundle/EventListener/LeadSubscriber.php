<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticOwnerManagerBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\ArrayHelper;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\Event\PointsChangeEvent;
use Mautic\LeadBundle\LeadEvents;
use MauticPlugin\MauticOwnerManagerBundle\Entity\OwnerManagerLog;
use MauticPlugin\MauticOwnerManagerBundle\Entity\OwnerManagerLogRepository;
use MauticPlugin\MauticOwnerManagerBundle\Event\OwnerManagerBuilderEvent;
use MauticPlugin\MauticOwnerManagerBundle\Event\OwnerManagerChangeActionExecutedEvent;
use MauticPlugin\MauticOwnerManagerBundle\Form\Type\ActionContactUpdateChangeType;
use MauticPlugin\MauticOwnerManagerBundle\Form\Type\ActionPointChangeType;
use MauticPlugin\MauticOwnerManagerBundle\Helper\MatchHelper;
use MauticPlugin\MauticOwnerManagerBundle\Helper\TimelineEventHelper;
use MauticPlugin\MauticOwnerManagerBundle\Model\OwnerManagerModel;
use MauticPlugin\MauticOwnerManagerBundle\OwnerManagerEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;

class LeadSubscriber implements EventSubscriberInterface
{
    const POINT_CHANGE         = 'point.change';

    const CONTACT_FIELD_CHANGE = 'contact.field.change';

    /**
     * @var OwnerManagerModel
     */
    private $ownerManagerModel;

    /**
     * @var TimelineEventHelper
     */
    private $timelineEventHelper;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * LeadSubscriber constructor.
     */
    public function __construct(OwnerManagerModel $ownerManagerModel, TimelineEventHelper $timelineEventHelper, TranslatorInterface $translator, EntityManager $em)
    {
        $this->ownerManagerModel   = $ownerManagerModel;
        $this->timelineEventHelper = $timelineEventHelper;
        $this->translator          = $translator;
        $this->em                  = $em;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            OwnerManagerEvents::OWNERMANAGER_ON_BUILD          => ['onOwnerManagerBuild', 0],
            OwnerManagerEvents::OWNERMANAGER_ON_ACTION_EXECUTE => ['onActionExecute', 0],
            LeadEvents::LEAD_POINTS_CHANGE                     => ['onLeadPointsChange', 0],
            LeadEvents::LEAD_POST_SAVE                         => ['onLeadPostSave', 0],
            LeadEvents::TIMELINE_ON_GENERATE                   => ['onTimelineGenerate', 0],
        ];
    }

    public function onOwnerManagerBuild(OwnerManagerBuilderEvent $event)
    {
        $action = [
            'group'       => 'mautic.ownermanager.lead.action',
            'label'       => 'mautic.ownermanager.action.pointchange',
            'description' => 'mautic.ownermanager.action.pointchange_desc',
            'eventName'   => OwnerManagerEvents::OWNERMANAGER_ON_ACTION_EXECUTE,
            'formType'    => ActionPointChangeType::class,
        ];

        $event->addAction(self::POINT_CHANGE, $action);

        $action = [
            'group'       => 'mautic.ownermanager.lead.action',
            'label'       => 'mautic.ownermanager.action.contactupdate',
            'description' => 'mautic.ownermanager.action.contactupdate_desc',
            'eventName'   => OwnerManagerEvents::OWNERMANAGER_ON_ACTION_EXECUTE,
            'formType'    => ActionContactUpdateChangeType::class,
        ];

        $event->addAction(self::CONTACT_FIELD_CHANGE, $action);
    }

    /**
     * @throws \Exception
     */
    public function onLeadPointsChange(PointsChangeEvent $event)
    {
        $this->ownerManagerModel->triggerAction(self::POINT_CHANGE, $event, $event->getLead());
    }

    /**
     * @throws \Exception
     */
    public function onLeadPostSave(LeadEvent $event)
    {
        $this->ownerManagerModel->triggerAction(self::CONTACT_FIELD_CHANGE, $event, $event->getLead());
    }

    /**
     * @return bool|void
     */
    public function onActionExecute(OwnerManagerChangeActionExecutedEvent $event)
    {
        if ($event->checkContext(self::POINT_CHANGE)) {
            $this->pointsChangeExecute($event);
        } elseif ($event->checkContext(self::CONTACT_FIELD_CHANGE)) {
            $this->contactFieldChangeExecute($event);
        }
    }

    private function contactFieldChangeExecute(OwnerManagerChangeActionExecutedEvent $event)
    {
        $field = ArrayHelper::getValue('field', $event->getOwnerManager()->getProperties());
        $value = ArrayHelper::getValue('value', $event->getOwnerManager()->getProperties());

        $contact = $event->getLead();

        $changedFields = $contact->getChanges(true);

        if (empty($changedFields)) {
            return;
        }

        if (!isset($changedFields[$field])) {
            return;
        }

        if (!isset($changedFields[$field][1])) {
            return;
        }

        if (!MatchHelper::matchRegexp($value, $changedFields[$field][1])) {
            return;
        }

        $repeatable = ArrayHelper::getValue('repeatable', $event->getOwnerManager()->getProperties());

        if ($repeatable) {
            $event->setSucceded();
        } else {
            $event->setSucceedIfNotAlreadyTriggered();
        }
    }

    private function pointsChangeExecute(OwnerManagerChangeActionExecutedEvent $event)
    {
        $points = ArrayHelper::getValue('points', $event->getOwnerManager()->getProperties());

        $pointEvent = $event->getEventDetails();

        if (!$pointEvent instanceof PointsChangeEvent) {
            return;
        }
        if ($pointEvent->getNewPoints() <= $points) {
            return;
        }
        $event->setSucceedIfNotAlreadyTriggered();
    }

    /**
     * Compile events for the lead timeline.
     */
    public function onTimelineGenerate(LeadTimelineEvent $event)
    {
        // Set available event types
        $eventTypeKey  = 'owner.changed';
        $eventTypeName = $this->translator->trans('mautic.ownermanager.event.changed');
        $event->addEventType($eventTypeKey, $eventTypeName);
        $event->addSerializerGroup('ownerManagerList');

        if (!$event->isApplicable($eventTypeKey)) {
            return;
        }

        /** @var OwnerManagerLogRepository $logRepository */
        $logRepository = $this->em->getRepository(OwnerManagerLog::class);
        $logs          = $logRepository->getLeadTimelineEvents($event->getLeadId(), $event->getQueryOptions());

        // Add to counter
        $event->addToCounter($eventTypeKey, $logs);

        if (!$event->isEngagementCount()) {
            // Add the logs to the event array
            foreach ($logs['results'] as $log) {
                $event->addEvent(
                    [
                        'event'      => $eventTypeKey,
                        'eventId'    => $eventTypeKey.$log['id'],
                        'eventLabel' => $this->timelineEventHelper->getLabel($log['eventName'], $log['actionName']),
                        'eventType'  => $eventTypeName,
                        'timestamp'  => $log['dateFired'],
                        'extra'      => [
                            'log' => $log,
                        ],
                        'icon'      => 'fa-users',
                        'contactId' => $log['lead_id'],
                    ]
                );
            }
        }
    }
}
