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

use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\FormBundle\Event\FormBuilderEvent;
use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\FormEvents;
use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Exception\InvalidValueException;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\InputOptionsDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\ObjectIdsDAO;
use Mautic\IntegrationsBundle\Sync\SyncService\SyncService;
use MauticPlugin\MauticYellowboxCrmBundle\Form\Type\PushContactActionType;
use MauticPlugin\MauticYellowboxCrmBundle\Integration\Provider\YellowboxSettingProvider;
use MauticPlugin\MauticYellowboxCrmBundle\Integration\YellowboxCrmIntegration;
use MauticPlugin\MauticYellowboxCrmBundle\YellowboxEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PushDataFormSubscriber implements EventSubscriberInterface
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
     * PushDataFormSubscriber constructor.
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
            FormEvents::FORM_ON_BUILD                        => ['configureAction', 0],
            YellowboxEvents::ON_FORM_ACTION_PUSH_CONTACT     => ['pushContacts', 0],
        ];
    }

    /**
     * @param CampaignBuilderEvent $event
     */
    public function configureAction(FormBuilderEvent $event)
    {
        if ($this->setting->isConfigured()) {
            $action = [
                'group'             => 'mautic.plugin.actions',
                'label'             => 'mautic.yellowbox.push.contact',
                'description'       => 'mautic.yellowbox.push.contact.desc',
                'formType'          => PushContactActionType::class,
                'eventName'         => YellowboxEvents::ON_FORM_ACTION_PUSH_CONTACT,
                'allowCampaignForm' => true,
            ];
            $event->addSubmitAction('contact.push_to_yellowbox', $action);
        }
    }

    /**
     * @throws \Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException
     * @throws \Mautic\IntegrationsBundle\Exception\InvalidValueException
     */
    public function pushContacts(SubmissionEvent $event)
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
        } catch (IntegrationNotFoundException $integrationNotFoundException) {
        } catch (InvalidValueException $invalidValueException) {
        }
    }
}
