<?php

return [
    'name'        => 'MauticExtendEmailFieldsBundle',
    'description' => '',
    'author'      => 'mtcextendee.com',
    'version'     => '1.0.0',
    'services'    => [
        'events' => [
            'mautic.extendee.email.settings.inject.custom.content.subscriber' => [
                'class'     => \MauticPlugin\MauticExtendEmailFieldsBundle\EventListener\InjectCustomContentSubscriber::class,
                'arguments' => [
                    'mautic.helper.templating',
                    'mautic.extendee.email.settings.model',
                    'request_stack',
                    'mautic.extendee.email.settings.integration.helper',
                ],
            ],
            'mautic.extendee.email.settings.email.subscriber'                 => [
                'class'     => \MauticPlugin\MauticExtendEmailFieldsBundle\EventListener\EmailSubscriber::class,
                'arguments' => [
                    'mautic.extendee.email.settings.model',
                    'mautic.extendee.email.settings.integration.helper',
                ],
            ],
            'mautic.extendee.email.settings.report.subscriber'                => [
                'class'     => \MauticPlugin\MauticExtendEmailFieldsBundle\EventListener\ReportSubscriber::class,
                'arguments' => [
                    'doctrine.dbal.default_connection',
                    'mautic.extendee.email.settings.integration.helper',
                ],
            ],
        ],
        'models' => [
            'mautic.extendee.email.settings.model' => [
                'class'     => 'MauticPlugin\MauticExtendEmailFieldsBundle\Model\ExtendEmailFieldsModel',
                'arguments' => [
                    'request_stack',
                ],
            ],
        ],
        'others' => [
            'mautic.extendee.email.settings.integration.helper' => [
                'class'     => \MauticPlugin\MauticExtendEmailFieldsBundle\Helper\ExtendeEmailFieldsIntegrationHelper::class,
                'arguments' => [
                    'mautic.helper.integration',
                    'translator',
                ],
            ],
        ],
        'integrations' => [
            'mautic.integration.extendemailfields' => [
                'class'     => \MauticPlugin\MauticExtendEmailFieldsBundle\Integration\ExtendEmailFieldsIntegration::class,
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
    ],
    'routes'      => [
        'api' => [
            'mautic_extend_email_fields_api' => [
                'path'       => '/extendemailfields/{emailId}',
                'controller' => 'MauticExtendEmailFieldsBundle:Api\ExtendEmailFieldsApi:add',
                'method'     => 'POST',
            ],
        ],
    ],
];
