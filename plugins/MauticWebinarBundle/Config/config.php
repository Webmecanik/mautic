<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      WebMekanik
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'name'        => 'Webinar',
    'description' => 'Enables integration with Mautic supported Webinar tools.',
    'version'     => '1.0',
    'author'      => 'WebMekanik',
    'services'    => [
        'integrations' => [
            'mautic.integration.webikeo' => [
                'class'     => \MauticPlugin\MauticWebinarBundle\Integration\WebikeoIntegration::class,
                'arguments' => [
                    'event_dispatcher',
                    'mautic.helper.cache_storage',
                    'doctrine.orm.entity_manager',
                    'session',
                    'request_stack',
                    'router',
                    'translator',
                    'logger',
                    'mautic.helper.encryption',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.company',
                    'mautic.helper.paths',
                    'mautic.core.model.notification',
                    'mautic.lead.model.field',
                    'mautic.plugin.model.integration_entity',
                    'mautic.lead.model.dnc',
                ],
            ],
        ],
        'events' => [
            'mautic.webinar.campaignbundle.subscriber' => [
                'class'     => MauticPlugin\MauticWebinarBundle\EventListener\CampaignSubscriber::class,
                'arguments' => [
                    'mautic.helper.integration',
                    'translator',
                ],
            ],
            'mautic.webinar.formbundle.subscriber' => [
                'class'     => MauticPlugin\MauticWebinarBundle\EventListener\FormSubscriber::class,
                'arguments' => [
                    'mautic.helper.integration',
                    'translator',
                ],
            ],
            'mautic.webinar.leadbundle.subscriber' => [
                'class'     => MauticPlugin\MauticWebinarBundle\EventListener\LeadListSubscriber::class,
                'arguments' => [
                    'mautic.helper.integration',
                    'mautic.lead.model.list',
                    'translator',
                ],
            ],
        ],
        'forms' => [
            'mautic.form.type.webikeo.campaigncondition' => [
                'class'     => MauticPlugin\MauticWebinarBundle\Form\Type\WebikeoCampaignWebinarsType::class,
                'alias'     => 'Webikeo_campaignevent_webinars',
                'arguments' => [
                    'mautic.helper.integration',
                ],
            ],
        ],
    ],
];
