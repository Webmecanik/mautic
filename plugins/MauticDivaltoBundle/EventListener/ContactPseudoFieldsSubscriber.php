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

use Mautic\IntegrationsBundle\Entity\ObjectMappingRepository;
use Mautic\IntegrationsBundle\Event\InternalContactProcessPseudFieldsEvent;
use Mautic\IntegrationsBundle\IntegrationEvents;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\InputOptionsDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\ObjectIdsDAO;
use Mautic\IntegrationsBundle\Sync\SyncService\SyncService;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\MauticDivaltoBundle\Integration\DivaltoIntegration;
use MauticPlugin\MauticDivaltoBundle\Sync\Mapping\Manual\MappingManualFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ContactPseudoFieldsSubscriber implements EventSubscriberInterface
{
    /**
     * @var LeadModel
     */
    private $leadModel;

    /**
     * @var ObjectMappingRepository
     */
    private $objectMappingRepository;

    /**
     * @var SyncService
     */
    private $syncService;

    /**
     * ContactPseudoFieldsSubscriber constructor.
     */
    public function __construct(LeadModel $leadModel, ObjectMappingRepository $objectMappingRepository, SyncService $syncService)
    {
        $this->leadModel               = $leadModel;
        $this->objectMappingRepository = $objectMappingRepository;
        $this->syncService             = $syncService;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            IntegrationEvents::INTEGRATION_CONTACT_PROCESS_PSEUDO_FIELDS => ['processPseudoFields', 0],
        ];
    }

    public function processPseudoFields(InternalContactProcessPseudFieldsEvent $processPseudFieldsEvent)
    {
        if (DivaltoIntegration::NAME !== $processPseudFieldsEvent->getIntegration()) {
            return;
        }

        $fields = $processPseudFieldsEvent->getFields();
        foreach ($fields as $name=>$field) {
            if ('company_id' === $name) {
                $value          = $field->getValue()->getNormalizedValue();
                $internalObject = $this->objectMappingRepository->getInternalObject(DivaltoIntegration::NAME, MappingManualFactory::COMPANY_OBJECT, $value, 'company');

                if (empty($internalObject)) {
                    $integrationObjectIds = new ObjectIdsDAO();
                    $integrationObjectIds->addObjectId(MappingManualFactory::COMPANY_OBJECT, (string) $value);

                    $inputOptions = new InputOptionsDAO(
                        [
                            'integration'           => DivaltoIntegration::NAME,
                            'disable-push'          => true,
                            'integration-object-id' => $integrationObjectIds,
                        ]
                    );
                    $this->syncService->processIntegrationSync($inputOptions);

                    $internalObject = $this->objectMappingRepository->getInternalObject(DivaltoIntegration::NAME, MappingManualFactory::COMPANY_OBJECT, $value, 'company');
                }

                if (!empty($internalObject)) {
                    $this->leadModel->addToCompany($processPseudFieldsEvent->getContact(), $internalObject['internal_object_id']);
                    $this->leadModel->setPrimaryCompany($internalObject['internal_object_id'], $processPseudFieldsEvent->getContact()->getId());
                }
            }
        }
    }
}
