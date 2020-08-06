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

namespace MauticPlugin\MauticYellowboxCrmBundle\Sync;

use Mautic\IntegrationsBundle\Sync\Logger\DebugLogger;
use MauticPlugin\MauticYellowboxCrmBundle\Integration\Provider\YellowboxSettingProvider;
use MauticPlugin\MauticYellowboxCrmBundle\Integration\YellowboxCrmIntegration;
use MauticPlugin\MauticYellowboxCrmBundle\Service\LeadEventSupplier;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\Event;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\EventFactory;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository\EventRepository;

/**
 * Class AccountDataExchange.
 */
class EventSyncService
{
    /**
     * @var LeadEventSupplier
     */
    private $leadEventSupplier;

    /**
     * @var EventRepository
     */
    private $eventRepository;

    /**
     * @var YellowboxSettingProvider
     */
    private $settingProvider;

    public function __construct(LeadEventSupplier $leadEventSupplier, EventRepository $eventRepository, YellowboxSettingProvider $settingProvider)
    {
        $this->leadEventSupplier = $leadEventSupplier;
        $this->eventRepository   = $eventRepository;
        $this->settingProvider   = $settingProvider;
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidQueryArgumentException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\YellowboxPluginException
     */
    public function sync(?\DateTimeInterface $dateFrom, ?\DateTimeInterface $dateTo): void
    {
        if (!$this->settingProvider->isActivitySyncEnabled()) {
            return;
        }

        $mapping = $this->leadEventSupplier->getLeadsMapping();

        if (!count($mapping)) {
            DebugLogger::log(YellowboxCrmIntegration::NAME, 'No mapped contacts to synchronize activities for.');

            return;
        }

        $this->settingProvider->exceptConfigured();

        $eventsToSynchronize = $this->getSyncReport($mapping, $this->settingProvider->getActivityEvents(), $dateFrom, $dateTo);

        DebugLogger::log(YellowboxCrmIntegration::NAME, sprintf('Uploading %d Events', count($eventsToSynchronize['up'])));

        $iter = 0;
        foreach ($eventsToSynchronize['up'] as $event) {
            DebugLogger::log(YellowboxCrmIntegration::NAME,
                sprintf('Creating %s [%d%%] %d of %d ',
                    'event',
                    round(100 * (++$iter / count($eventsToSynchronize['up']))),
                    $iter,
                    count($eventsToSynchronize['up'])
                ));
            $this->eventRepository->create($event);
        }

        DebugLogger::log(YellowboxCrmIntegration::NAME, 'Events have been uploaded');
    }

    /**
     * @param      $mappings
     * @param null $dateFrom
     * @param null $dateTo
     *
     * @return array
     *
     * @throws \Exception
     */
    private function getSyncReport($mappings, array $events = [], $dateFrom = null, $dateTo = null)
    {
        $mauticEvents = $this->leadEventSupplier->getLeadEvents(array_keys($mappings), $events, $dateFrom, $dateTo);

        $yellowboxEvents = $this->eventRepository->findByContactIds($mappings);

        $eventTypesFlipped = array_flip($this->leadEventSupplier->getTypes());
        $eventTypes        = $this->leadEventSupplier->getTypes();

        $result = ['up' => [], 'down' => []];

        $yellowboxCheck = [];
        /** @var Event $yellowboxEvent */
        foreach ($yellowboxEvents as $yellowboxEvent) {
            if (!isset($eventTypesFlipped[$yellowboxEvent->getSubject()])) {
                continue;
            }

            $yellowboxCheck[$yellowboxEvent->getContactId()][$yellowboxEvent->getDateTimeStart()->getTimestamp()][] = [
                'timestamp' => $yellowboxEvent->getDateTimeStart()->getTimestamp(),
                'message'   => $yellowboxEvent->getSubject(),
                'event'     => $eventTypesFlipped[$yellowboxEvent->getSubject()],
                'priority'  => $yellowboxEvent->getTaskPriority(),
            ];
        }

        $found = 0;
        foreach ($mauticEvents as $mauticLeadId => $leadEventsArray) {
            $yellowboxId = $mappings[$mauticLeadId] ?? false;
            if (!$yellowboxId) {   // Do not upload to not mapped contacts
                continue;
            }
            foreach ($leadEventsArray as $eventTimeStamp => $leadEvents) {
                foreach ($leadEvents as $event) {
                    $eventCheck = [
                        'timestamp' => $eventTimeStamp,
                        'message'   => $eventTypes[$event['event']],
                        'event'     => $event['event'],
                        'priority'  => $event['priority'],
                    ];

                    if (isset($yellowboxCheck[$yellowboxId][$eventTimeStamp]) && in_array($eventCheck, $yellowboxCheck[$yellowboxId][$eventTimeStamp])) {
                        ++$found;
                        continue;
                    }

                    $eventTime = new \DateTime();
                    $eventTime->setTimestamp($eventTimeStamp);
                    /** @var Event $event */
                    $event = EventFactory::createEmptyPrefilled();
                    $event->setContactId((string) $yellowboxId);
                    $event->setDateTimeStart($eventTime);
                    $event->setDateTimeEnd($eventTime);
                    $event->setSubject($eventCheck['message']);
                    $event->setTaskPriority($eventCheck['priority']);
                    $event->setAssignedUserId($this->settingProvider->getOwner());
                    $result['up'][] = $event;
                }
            }
        }

        return $result;
    }
}
