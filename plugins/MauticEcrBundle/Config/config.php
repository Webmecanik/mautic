<?php

return [
    'name'        => 'MauticEcrBundle',
    'description' => 'Ecr sync for Mautic',
    'version'     => '1.0',
    'author'      => 'MTCExtendee',

    'routes' => [
    ],

    'services'   => [
        'events'       => [
        ],
        'forms'        => [
        ],
        'models'       => [
        ],
        'integrations' => [
            'mautic.integration.ecr' => [
                'class'     => \MauticPlugin\MauticEcrBundle\Integration\EcrIntegration::class,
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
                ],
            ],
        ],
        'others'       => [
            'mautic.ecr.integration.settings' => [
                'class'     => \MauticPlugin\MauticEcrBundle\Integration\EcrSettings::class,
                'arguments' => [
                    'mautic.helper.integration',
                ],
            ],
            'mautic.ecr.sync' => [
                'class'     => \MauticPlugin\MauticEcrBundle\Sync\EcrSync::class,
                'arguments' => [
                    'mautic.http.connector',
                    'mautic.ecr.integration.settings',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.dnc',
                ],
            ],
        ],
        'controllers'  => [
        ],
        'commands'     => [
            'mautic.ecr.command.sync' => [
                'class'     => \MauticPlugin\MauticEcrBundle\Command\EcrSyncCommand::class,
                'arguments' => [
                    'mautic.ecr.sync',
                ],
                'tag'       => 'console.command',
            ],
        ],
    ],
    'parameters' => [
    ],
];
