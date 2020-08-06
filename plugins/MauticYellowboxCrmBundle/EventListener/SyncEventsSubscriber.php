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

namespace MauticPlugin\MauticYellowboxCrmBundle\EventListener;

use Mautic\IntegrationsBundle\Event\SyncEvent;
use Mautic\IntegrationsBundle\IntegrationEvents;
use MauticPlugin\MauticYellowboxCrmBundle\Integration\YellowboxCrmIntegration;
use MauticPlugin\MauticYellowboxCrmBundle\Sync\EventSyncService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class SyncSubscriber.
 */
class SyncEventsSubscriber implements EventSubscriberInterface
{
    /**
     * @var EventSyncService
     */
    private $eventSyncService;

    public function __construct(EventSyncService $eventSyncService)
    {
        $this->eventSyncService = $eventSyncService;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            IntegrationEvents::INTEGRATION_POST_EXECUTE => ['onPostExecuteOrder', 0],
        ];
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
    public function onPostExecuteOrder(SyncEvent $event): void
    {
        if (!$event->isIntegration(YellowboxCrmIntegration::NAME)) {
            return;
        }

        $this->eventSyncService->sync($event->getFromDateTime(), $event->getToDateTime());
    }
}
