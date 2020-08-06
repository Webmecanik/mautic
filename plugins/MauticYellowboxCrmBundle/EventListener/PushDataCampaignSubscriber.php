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

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Exception\InvalidValueException;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\InputOptionsDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\ObjectIdsDAO;
use Mautic\IntegrationsBundle\Sync\SyncService\SyncService;
use MauticPlugin\MauticYellowboxCrmBundle\Integration\Provider\YellowboxSettingProvider;
use MauticPlugin\MauticYellowboxCrmBundle\Integration\YellowboxCrmIntegration;
use MauticPlugin\MauticYellowboxCrmBundle\YellowboxEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PushDataCampaignSubscriber implements EventSubscriberInterface
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
     * PushDataCampaignSubscriber constructor.
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
            CampaignEvents::CAMPAIGN_ON_BUILD                => ['configureAction', 0],
            YellowboxEvents::ON_CAMPAIGN_ACTION_PUSH_CONTACT => ['pushContacts', 0],
        ];
    }

    public function configureAction(CampaignBuilderEvent $event)
    {
        if ($this->setting->isConfigured()) {
            $event->addAction(
                'contact.push_to_yellowbox',
                [
                    'group'          => 'mautic.lead.lead.submitaction',
                    'label'          => 'mautic.yellowbox.push.contact',
                    'description'    => 'mautic.yellowbox.push.contact.desc',
                    'batchEventName' => YellowboxEvents::ON_CAMPAIGN_ACTION_PUSH_CONTACT,
                ]
            );
        }
    }

    /**
     * @throws \Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException
     * @throws \Mautic\IntegrationsBundle\Exception\InvalidValueException
     */
    public function pushContacts(PendingEvent $event)
    {
        $contactIds = $event->getContactIds();
        try {
            $mauticObjectIds = new ObjectIdsDAO();
            foreach ($contactIds as $contactId) {
                $mauticObjectIds->addObjectId('lead', ''.$contactId);
            }

            $inputOptions = new InputOptionsDAO(
                [
                    'integration'      => YellowboxCrmIntegration::NAME,
                    'disable-pull'     => true,
                    'mautic-object-id' => $mauticObjectIds,
                ]
            );
            $this->syncService->processIntegrationSync($inputOptions);
            $event->passAll();
        } catch (IntegrationNotFoundException $integrationNotFoundException) {
            $event->failAll($integrationNotFoundException->getMessage());
        } catch (InvalidValueException $invalidValueException) {
            $event->failAll($invalidValueException->getMessage());
        }
    }
}
