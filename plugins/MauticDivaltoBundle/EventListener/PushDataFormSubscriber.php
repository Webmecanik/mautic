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

use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\FormBundle\Event\FormBuilderEvent;
use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\FormEvents;
use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Exception\InvalidValueException;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\InputOptionsDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\ObjectIdsDAO;
use Mautic\IntegrationsBundle\Sync\SyncService\SyncService;
use MauticPlugin\MauticDivaltoBundle\DivaltoEvents;
use MauticPlugin\MauticDivaltoBundle\Form\Type\PushContactActionType;
use MauticPlugin\MauticDivaltoBundle\Integration\Config;
use MauticPlugin\MauticDivaltoBundle\Integration\DivaltoIntegration;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PushDataFormSubscriber implements EventSubscriberInterface
{
    /**
     * @var SyncService
     */
    private $syncService;

    /**
     * @var Config
     */
    private $config;

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
            FormEvents::FORM_ON_BUILD                        => ['configureAction', 0],
            DivaltoEvents::ON_FORM_ACTION_PUSH_CONTACT       => ['pushContacts', 0],
        ];
    }

    /**
     * @param CampaignBuilderEvent $event
     */
    public function configureAction(FormBuilderEvent $event)
    {
        if ($this->config->isConfigured()) {
            $action = [
                'group'             => 'mautic.plugin.actions',
                'label'             => 'divalto.push.contact',
                'description'       => 'divalto.push.contact.desc',
                'formType'          => PushContactActionType::class,
                'eventName'         => DivaltoEvents::ON_FORM_ACTION_PUSH_CONTACT,
                'allowCampaignForm' => true,
            ];
            $event->addSubmitAction('contact.push_to_divalto', $action);
        }
    }

    public function pushContacts(SubmissionEvent $event)
    {
        try {
            $mauticObjectIds = new ObjectIdsDAO();
            $mauticObjectIds->addObjectId('lead', (string) $event->getLead()->getId());

            $inputOptions = new InputOptionsDAO(
                [
                    'integration'      => DivaltoIntegration::NAME,
                    'disable-pull'     => true,
                    'mautic-object-id' => $mauticObjectIds,
                ]
            );
            $this->syncService->processIntegrationSync($inputOptions);
        } catch (IntegrationNotFoundException $integrationNotFoundException) {
        } catch (InvalidValueException $invalidValueException) {
        }
    }
}
