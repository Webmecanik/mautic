<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticDivaltoBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Exception\InvalidValueException;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\InputOptionsDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\ObjectIdsDAO;
use Mautic\IntegrationsBundle\Sync\SyncService\SyncService;
use MauticPlugin\MauticDivaltoBundle\DivaltoEvents;
use MauticPlugin\MauticDivaltoBundle\Integration\Config;
use MauticPlugin\MauticDivaltoBundle\Integration\DivaltoIntegration;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PushDataCampaignSubscriber implements EventSubscriberInterface
{
    /**
     * @var SyncService
     */
    private $syncService;

    /**
     * @var Config
     */
    private $config;

    /**
     * PushDataCampaignSubscriber constructor.
     */
    public function __construct(SyncService $syncService, Config $config)
    {
        $this->syncService = $syncService;
        $this->config      = $config;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD                => ['configureAction', 0],
            DivaltoEvents::ON_CAMPAIGN_ACTION_PUSH_CONTACT   => ['pushContacts', 0],
        ];
    }

    public function configureAction(CampaignBuilderEvent $event)
    {
        if ($this->config->isConfigured()) {
            $event->addAction(
                'contact.push_to_divalto',
                [
                    'group'          => 'mautic.lead.lead.submitaction',
                    'label'          => 'divalto.push.contact',
                    'description'    => 'divalto.push.contact.desc',
                    'batchEventName' => DivaltoEvents::ON_CAMPAIGN_ACTION_PUSH_CONTACT,
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
                    'integration'      => DivaltoIntegration::NAME,
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
