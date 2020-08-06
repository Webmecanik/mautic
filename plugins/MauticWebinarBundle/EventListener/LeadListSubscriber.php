<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticWebinarBundle\EventListener;

use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Event\LeadListFiltersChoicesEvent;
use Mautic\LeadBundle\Event\ListPreProcessListEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class LeadListsSubscriber.
 */
class LeadListSubscriber implements EventSubscriberInterface
{
    /**
     * @var IntegrationHelper
     */
    protected $helper;

    protected $listModel;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * LeadListSubscriber constructor.
     */
    public function __construct(IntegrationHelper $helper, ListModel $listModel, TranslatorInterface $translator)
    {
        $this->helper     = $helper;
        $this->listModel  = $listModel;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::LIST_FILTERS_CHOICES_ON_GENERATE => ['onFilterChoiceFieldsGenerate', 0],
            LeadEvents::LIST_PRE_PROCESS_LIST            => ['onLeadListPreProcessList', 0],
        ];
    }

    public function onFilterChoiceFieldsGenerate(LeadListFiltersChoicesEvent $event)
    {
        $services = $this->helper->getIntegrationObjects();
        $choices  = [];
        foreach ($services as $integration) {
            if (!$integration || !$integration->getIntegrationSettings()->isPublished()) {
                continue;
            }
            if (method_exists($integration, 'getSubscribersForSegmentProcessing')) {
                $webinars = $integration->getWebinars([], true);
                if ($webinars) {
                    $integrationName = $integration->getName();
                    array_walk(
                            $webinars,
                            function (&$choice) use ($integrationName) {
                                $choice['value'] = $integrationName.'::'.$choice['value'];
                            }
                        );

                    $choices[$integrationName] = $webinars;
                }
            }
        }

        if (!empty($choices)) {
            $configAttended = [
                'label'      => $this->translator->trans('mautic.plugin.webinar.webinars.attended'),
                'properties' => ['type' => 'select', 'list' => $choices],
                'operators'  => $this->listModel->getOperatorsForFieldType(
                    [
                        'include' => ['='],
                    ]
                ),
                'object' => 'lead',
            ];
            $configNotAttended = [
                'label'      => $this->translator->trans('mautic.plugin.webinar.webinars.not.attended'),
                'properties' => ['type' => 'select', 'list' => $choices],
                'operators'  => $this->listModel->getOperatorsForFieldType(
                    [
                        'include' => ['='],
                    ]
                ),
                'object' => 'lead',
            ];
            $configSubscribed= [
                'label'      => $this->translator->trans('mautic.plugin.webinar.webinars.subscribed'),
                'properties' => ['type' => 'select', 'list' => $choices],
                'operators'  => $this->listModel->getOperatorsForFieldType(
                    [
                        'include' => ['='],
                    ]
                ),
                'object' => 'lead',
            ];

            $event->addChoice('lead', 'webinar_attended', $configAttended);
            $event->addChoice('lead', 'webinar_not_attended', $configNotAttended);
            $event->addChoice('lead', 'webinar_subscribed', $configSubscribed);
        }
    }

    /**
     * Add/remove contacts to a segment based on contacts.
     *
     * @return ListPreProcessListEvent
     */
    public function onLeadListPreProcessList(ListPreProcessListEvent $event)
    {
        $list      = $event->getList();
        $success   = false;
        $filters   = ($list instanceof LeadList) ? $list->getFilters() : $list['filters'];
        $segmentId = ($list instanceof LeadList) ? $list->getId() : $list['id'];

        foreach ($filters as $filter) {
            if (in_array($filter['field'], ['webinar_attended', 'webinar_not_attended', 'webinar_subscribed'])) {
                [$integrationName, $webinarId] = explode('::', $filter['filter']);

                if ($integrationObject = $this->helper->getIntegrationObject($integrationName)) {
                    if (!$integrationObject->getIntegrationSettings()->isPublished() || !method_exists($integrationObject, 'getSubscribersForSegmentProcessing')) {
                        continue;
                    }
                }

                switch ($filter['field']) {
                    case 'webinar_attended':
                        $isNoShow = 'false';
                        if ($integrationObject->getSubscribersForSegmentProcessing($webinarId, $isNoShow, $segmentId)) {
                            $success = true;
                        }
                        break;
                    case 'webinar_not_attended':
                        $isNoShow = 'true';
                        if ($integrationObject->getSubscribersForSegmentProcessing($webinarId, $isNoShow, $segmentId)) {
                            $success = true;
                        }
                        break;
                    case 'webinar_subscribed':
                        if ($integrationObject->getSubscribersForSegmentProcessing($webinarId, null, $segmentId)) {
                            $success = true;
                        }
                        break;
                }
            }
        }

        return $event->setResult($success);
    }
}
