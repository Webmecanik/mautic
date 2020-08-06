<?php

return [
    'name'        => 'MauticContactOverviewBundle',
    'description' => 'Contact history overview for Mautic',
    'version'     => '1.0',
    'author'      => 'MTCExtendee',

    'routes' => [
        'public' => [
            'mautic_contactoverview_events' => [
                'path'         => '/contact/overview/{hash}/{page}',
                'controller'   => 'MauticContactOverviewBundle:Contact:overview',
                'requirements' => [
                ],
            ],
        ],
    ],

    'services'   => [
        'events'       => [
            'mautic.contactoverview.email.subscriber' => [
                'class'     => \MauticPlugin\MauticContactOverviewBundle\EventListener\EmailSubscriber::class,
                'arguments' => [
                    'mautic.contactoverview.integration.settings',
                    'translator',
                    'router',
                ],
            ],
        ],
        'forms'        => [
        ],
        'models'       => [
        ],
        'integrations' => [
            'mautic.integration.contactoverview' => [
                'class'     => \MauticPlugin\MauticContactOverviewBundle\Integration\ContactOverviewIntegration::class,
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
        'others'       => [
            'mautic.contactoverview.integration.settings' => [
                'class'     => \MauticPlugin\MauticContactOverviewBundle\Integration\ContactOverviewSettings::class,
                'arguments' => [
                    'mautic.helper.integration',
                    'mautic.helper.encryption',
                ],
            ],
        ],
        'controllers'  => [
        ],
        'commands'     => [
        ],
    ],
    'parameters' => [
    ],
];
