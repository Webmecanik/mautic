<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticYellowboxCrmBundle\EventListener;

use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Exception\InvalidValueException;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\InputOptionsDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\ObjectIdsDAO;
use Mautic\IntegrationsBundle\Sync\SyncService\SyncService;
use Mautic\PointBundle\Event\TriggerBuilderEvent;
use Mautic\PointBundle\Event\TriggerExecutedEvent;
use Mautic\PointBundle\PointEvents;
use MauticPlugin\MauticYellowboxCrmBundle\Form\Type\PushContactActionType;
use MauticPlugin\MauticYellowboxCrmBundle\Integration\Provider\YellowboxSettingProvider;
use MauticPlugin\MauticYellowboxCrmBundle\Integration\YellowboxCrmIntegration;
use MauticPlugin\MauticYellowboxCrmBundle\YellowboxEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PushDataPointSubscriber implements EventSubscriberInterface
{
    /**
     * @var SyncService
     */
    private $syncService;

    /**
     * @var YellowboxSettingProvider
     */
    private $setting;

    /**
     * PushDataPointSubscriber constructor.
     */
    public function __construct(SyncService $syncService, YellowboxSettingProvider $setting)
    {
        $this->syncService = $syncService;
        $this->setting     = $setting;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            PointEvents::TRIGGER_ON_BUILD                  => ['configureTrigger', 0],
            YellowboxEvents::ON_POINT_TRIGGER_PUSH_CONTACT => ['pushContacts', 0],
        ];
    }

    public function configureTrigger(TriggerBuilderEvent $event)
    {
        if ($this->setting->isConfigured()) {
            $action = [
                'group'       => 'mautic.plugin.point.action',
                'label'       => 'mautic.yellowbox.push.contact',
                'description' => 'mautic.yellowbox.push.contact.desc',
                'formType'    => PushContactActionType::class,
                'eventName'   => YellowboxEvents::ON_POINT_TRIGGER_PUSH_CONTACT,
            ];
            $event->addEvent('contact.push_to_yellowbox', $action);
        }
    }

    /**
     * @throws \Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException
     * @throws \Mautic\IntegrationsBundle\Exception\InvalidValueException
     */
    public function pushContacts(TriggerExecutedEvent $event)
    {
        try {
            $mauticObjectIds = new ObjectIdsDAO();
            $mauticObjectIds->addObjectId('lead', (string) $event->getLead()->getId());

            $inputOptions = new InputOptionsDAO(
                [
                    'integration'      => YellowboxCrmIntegration::NAME,
                    'disable-pull'     => true,
                    'mautic-object-id' => $mauticObjectIds,
                ]
            );
            $this->syncService->processIntegrationSync($inputOptions);
            $event->setSucceded();
        } catch (IntegrationNotFoundException $integrationNotFoundException) {
            $event->setFailed();
        } catch (InvalidValueException $invalidValueException) {
            $event->setFailed();
        }
    }
}
