<?php

declare(strict_types=1);

return [
    'name'        => 'Divalto',
    'description' => 'Divalto CRM integration for Mautic',
    'version'     => '1.0.1',
    'author'      => 'Webmecanik',
    'routes'      => [
        'main'   => [],
        'public' => [],
        'api'    => [],
    ],
    'menu'        => [],
    'services'    => [
        'events' => [
            'mautic.divalto.push_data.form.subscriber' => [
                'class'     => \MauticPlugin\MauticDivaltoBundle\EventListener\PushDataFormSubscriber::class,
                'arguments' => [
                    'mautic.integrations.sync.service',
                    'divalto.config',
                ],
            ],
            'mautic.divalto.push_data.campaign.subscriber' => [
                'class'     => \MauticPlugin\MauticDivaltoBundle\EventListener\PushDataCampaignSubscriber::class,
                'arguments' => [
                    'mautic.integrations.sync.service',
                    'divalto.config',
                ],
            ],
            'mautic.divalto.push_data.point.subscriber' => [
                'class'     => \MauticPlugin\MauticDivaltoBundle\EventListener\PushDataPointSubscriber::class,
                'arguments' => [
                    'mautic.integrations.sync.service',
                    'divalto.config',
                ],
            ],
            'divalto.subscriber.ui_contact_integrations_tab' => [
                'class'     => \MauticPlugin\MauticDivaltoBundle\EventListener\UIContactIntegrationsTabSubscriber::class,
            ],
            'divalto.subscriber.pseudo_fields' => [
                'class'     => \MauticPlugin\MauticDivaltoBundle\EventListener\ContactPseudoFieldsSubscriber::class,
                'arguments' => [
                    'mautic.lead.model.lead',
                    'mautic.integrations.repository.object_mapping',
                    'mautic.integrations.sync.service',
                ],
            ],
        ],
        'other'        => [
            // Provides access to configured API keys, settings, field mapping, etc
            'divalto.config'            => [
                'class'     => \MauticPlugin\MauticDivaltoBundle\Integration\Config::class,
                'arguments' => [
                    'mautic.integrations.helper',
                ],
            ],
            // Configuration for the http client which includes where to persist tokens
            'divalto.connection.config' => [
                'class'     => \MauticPlugin\MauticDivaltoBundle\Connection\Config::class,
                'arguments' => [
                    'mautic.integrations.auth_provider.token_persistence_factory',
                ],
            ],
            // The http client used to communicate with the integration which in this case uses OAuth2 client_credentials grant
            'divalto.connection.client' => [
                'class'     => \MauticPlugin\MauticDivaltoBundle\Connection\Client::class,
                'arguments' => [
                    'mautic.integrations.auth_provider.oauth2twolegged',
                    'divalto.config',
                    'divalto.connection.config',
                    'monolog.logger.mautic',
                ],
            ],
        ],
        'sync'         => [
            // Returns available fields from the integration either from cache or "live" via API
            'divalto.sync.repository.fields'      => [
                'class'     => \MauticPlugin\MauticDivaltoBundle\Sync\Mapping\Field\FieldRepository::class,
                'arguments' => [
                    'mautic.helper.cache_storage',
                    'divalto.connection.client',
                ],
            ],
            // Creates the instructions to the sync engine for which objects and fields to sync and direction of data flow
            'divalto.sync.mapping_manual.factory' => [
                'class'     => \MauticPlugin\MauticDivaltoBundle\Sync\Mapping\Manual\MappingManualFactory::class,
                'arguments' => [
                    'divalto.sync.repository.fields',
                    'divalto.config',
                ],
            ],
            // Proxies the actions of the sync between Mautic and this integration to the appropriate services
            'divalto.sync.data_exchange' => [
                'class'     => \MauticPlugin\MauticDivaltoBundle\Sync\DataExchange\SyncDataExchange::class,
                'arguments' => [
                    'divalto.sync.data_exchange.report_builder',
                    'divalto.sync.data_exchange.order_executioner',
                ],
            ],
            // Builds a report of updated and new objects from the integration to sync with Mautic
            'divalto.sync.data_exchange.report_builder' => [
                'class'     => \MauticPlugin\MauticDivaltoBundle\Sync\DataExchange\ReportBuilder::class,
                'arguments' => [
                    'divalto.connection.client',
                    'divalto.config',
                    'divalto.sync.repository.fields',
                ],
            ],
            // Pushes updated or new Mautic contacts or companies to the integration
            'divalto.sync.data_exchange.order_executioner' => [
                'class'     => \MauticPlugin\MauticDivaltoBundle\Sync\DataExchange\OrderExecutioner::class,
                'arguments' => [
                    'divalto.connection.client',
                    'divalto.sync.repository.fields',
                    'mautic.lead.model.dnc',
                    'divalto.config',
                ],
            ],
        ],
        'integrations' => [
            // Basic definitions with name, display name and icon
            'mautic.integration.divalto' => [
                'class' => \MauticPlugin\MauticDivaltoBundle\Integration\DivaltoIntegration::class,
                'tags'  => [
                    'mautic.integration',
                    'mautic.basic_integration',
                ],
            ],
            // Provides the form types to use for the configuration UI
            'divalto.integration.configuration' => [
                'class'     => \MauticPlugin\MauticDivaltoBundle\Integration\Support\ConfigSupport::class,
                'arguments' => [
                    'divalto.sync.repository.fields',
                ],
                'tags'      => [
                    'mautic.config_integration',
                ],
            ],
            // Defines the mapping manual and sync data exchange service for the sync engine
            'divalto.integration.sync'          => [
                'class'     => \MauticPlugin\MauticDivaltoBundle\Integration\Support\SyncSupport::class,
                'arguments' => [
                    'divalto.sync.mapping_manual.factory',
                    'divalto.sync.data_exchange',
                ],
                'tags'      => [
                    'mautic.sync_integration',
                ],
            ],
        ],
    ],
];
