<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'name'        => 'Yellowbox CRM',
    'description' => 'Enables Yellowbox CRM integration',
    'version'     => '2.0',
    'author'      => 'Mautic',
    'services'    => [
        'events'       => [
            'mautic.yellowbox_crm.subscriber.events_sync' => [
                'class'     => \MauticPlugin\MauticYellowboxCrmBundle\EventListener\SyncEventsSubscriber::class,
                'arguments' => [
                    'mautic.yellowbox_crm.sync.events_service',
                ],
            ],
            'mautic.yellowbox_crm.subscriber.config_form_load' => [
                'class'     => \MauticPlugin\MauticYellowboxCrmBundle\EventListener\ConfigFormLoadSubscriber::class,
                'arguments' => [
                    'mautic.yellowbox_crm.cache.field_cache',
                ],
            ],
            'mautic.yellowbox_crm.push_data.campaign.subscriber' => [
                'class'     => \MauticPlugin\MauticYellowboxCrmBundle\EventListener\PushDataCampaignSubscriber::class,
                'arguments' => [
                    'mautic.integrations.sync.service',
                    'mautic.yellowbox_crm.settings',
                ],
            ],
            'mautic.yellowbox_crm.push_data.form.subscriber' => [
                'class'     => \MauticPlugin\MauticYellowboxCrmBundle\EventListener\PushDataFormSubscriber::class,
                'arguments' => [
                    'mautic.integrations.sync.service',
                    'mautic.yellowbox_crm.settings',
                ],
            ],
            'mautic.yellowbox_crm.push_data.point.subscriber' => [
                'class'     => \MauticPlugin\MauticYellowboxCrmBundle\EventListener\PushDataPointSubscriber::class,
                'arguments' => [
                    'mautic.integrations.sync.service',
                    'mautic.yellowbox_crm.settings',
                ],
            ],
        ],
        'validators' => [
            'mautic.yellowbox_crm.validator.connection_validator' => [
                'class'     => \MauticPlugin\MauticYellowboxCrmBundle\Validator\Constraints\ConnectionValidator::class,
                'arguments' => [
                    'mautic.yellowbox_crm.connection',
                    'translator',
                ],
                'tags' => [
                    'name' => 'validator.constraint_validator',
                ],
            ],
        ],
        'forms'        => [
            'mautic.yellowbox_crm.form.config_auth' => [
                'class'     => \MauticPlugin\MauticYellowboxCrmBundle\Form\Type\ConfigAuthType::class,
                'arguments' => [
                    'mautic.yellowbox_crm.connection',
                ],
            ],
            'mautic.yellowbox_crm.form.config_features' => [
                'class'     => \MauticPlugin\MauticYellowboxCrmBundle\Form\Type\ConfigSyncFeaturesType::class,
                'arguments' => [
                    'mautic.yellowbox_crm.repository.users',
                    'mautic.helper.core_parameters',
                ],
            ],
        ],
        'helpers'      => [
        ],
        'other'        => [
            'mautic.yellowbox.guzzle_http.client'                   => [
                'class' => GuzzleHttp\Client::class,
            ],
            'mautic.yellowbox_crm.settings'                  => [
                'class'     => \MauticPlugin\MauticYellowboxCrmBundle\Integration\Provider\YellowboxSettingProvider::class,
                'arguments' => [
                    'mautic.integrations.helper',
                ],
            ],
            'mautic.yellowbox_crm.connection'                => [
                'class'     => \MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Connection::class,
                'arguments' => [
                    'mautic.yellowbox.guzzle_http.client',
                    'mautic.yellowbox_crm.settings',
                ],
            ],
            'mautic.yellowbox_crm.transformer.yellowbox2mautic' => [
                'class'     => \MauticPlugin\MauticYellowboxCrmBundle\Sync\ValueNormalizer\Transformers\YellowboxMauticTransformer::class,
                'arguments' => ['mautic.yellowbox_crm.helper.ownerMauticSync'],
            ],
            'mautic.yellowbox_crm.transformer.mautic2yellowbox' => [
                'class'     => \MauticPlugin\MauticYellowboxCrmBundle\Sync\ValueNormalizer\Transformers\MauticYellowboxTransformer::class,
                'arguments' => [],
            ],
            'mautic.yellowbox_crm.value_normalizer'          => [
                'class'     => \MauticPlugin\MauticYellowboxCrmBundle\Sync\ValueNormalizer\YellowboxValueNormalizer::class,
                'arguments' => [
                    'mautic.yellowbox_crm.transformer.yellowbox2mautic',
                    'mautic.yellowbox_crm.transformer.mautic2yellowbox',
                ],
            ],
            'mautic.yellowbox_crm.validator.general'         => [
                'class'     => \MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\Validator\GeneralValidator::class,
                'arguments' => ['mautic.yellowbox_crm.repository.users'],
            ],
            'mautic.yellowbox_crm.validator.contact'         => [
                'class'     => \MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\Validator\ContactValidator::class,
                'arguments' => ['mautic.yellowbox_crm.repository.contacts', 'mautic.yellowbox_crm.validator.general'],
            ],
            'mautic.yellowbox_crm.repository.contacts'       => [
                'class'     => \MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository\ContactRepository::class,
                'arguments' => [
                    'mautic.yellowbox_crm.connection',
                    'mautic.yellowbox_crm.cache.field_cache',
                    'mautic.yellowbox_crm.modelFactory',
                    'mautic.yellowbox_crm.fieldDirectionFactory',
                    'mautic.yellowbox_crm.settings',
                ],
            ],
            'mautic.yellowbox_crm.validator.lead'            => [
                'class'     => \MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\Validator\LeadValidator::class,
                'arguments' => ['mautic.yellowbox_crm.repository.leads', 'mautic.yellowbox_crm.validator.general'],
            ],

            'mautic.yellowbox_crm.repository.leads'           => [
                'class'     => \MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository\LeadRepository::class,
                'arguments' => [
                    'mautic.yellowbox_crm.connection',
                    'mautic.yellowbox_crm.cache.field_cache',
                    'mautic.yellowbox_crm.modelFactory',
                    'mautic.yellowbox_crm.fieldDirectionFactory',
                    'mautic.yellowbox_crm.settings',
                ],
            ],
            'mautic.yellowbox_crm.cache.field_cache' => [
                'class'     => \MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository\Cache\FieldCache::class,
                'arguments' => [
                    'mautic.helper.cache_storage',
                ],
            ],
            'mautic.yellowbox_crm.validator.account'          => [
                'class'     => \MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\Validator\AccountValidator::class,
                'arguments' => ['mautic.yellowbox_crm.repository.accounts', 'mautic.yellowbox_crm.validator.general'],
            ],

            'mautic.yellowbox_crm.repository.accounts'   => [
                'class'     => \MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository\AccountRepository::class,
                'arguments' => [
                    'mautic.yellowbox_crm.connection',
                    'mautic.yellowbox_crm.cache.field_cache',
                    'mautic.yellowbox_crm.modelFactory',
                    'mautic.yellowbox_crm.fieldDirectionFactory',
                    'mautic.yellowbox_crm.settings',
                ],
            ],
            'mautic.yellowbox_crm.repository.events'     => [
                'class'     => \MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository\EventRepository::class,
                'arguments' => [
                    'mautic.yellowbox_crm.connection',
                    'mautic.yellowbox_crm.cache.field_cache',
                    'mautic.yellowbox_crm.modelFactory',
                    'mautic.yellowbox_crm.fieldDirectionFactory',
                    'mautic.yellowbox_crm.settings',
                ],
            ],
            'mautic.yellowbox_crm.repository.users'      => [
                'class'     => \MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository\UserRepository::class,
                'arguments' => [
                    'mautic.yellowbox_crm.connection',
                    'mautic.yellowbox_crm.cache.field_cache',
                    'mautic.yellowbox_crm.modelFactory',
                    'mautic.yellowbox_crm.fieldDirectionFactory',
                    'mautic.yellowbox_crm.settings',
                ],
            ],
            'mautic.yellowbox_crm.mapping.field_mapping' => [
                'class'     => \MauticPlugin\MauticYellowboxCrmBundle\Mapping\ObjectFieldMapper::class,
                'arguments' => [
                    'mautic.yellowbox_crm.settings',
                    'mautic.yellowbox_crm.repository.contacts',
                    'mautic.yellowbox_crm.repository.leads',
                    'mautic.yellowbox_crm.repository.accounts',
                ],
            ],
            'mautic.yellowbox_crm.sync.data_exchange'    => [
                'class'     => \MauticPlugin\MauticYellowboxCrmBundle\Sync\DataExchange::class,
                'arguments' => [
                    'mautic.yellowbox_crm.mapping.field_mapping',
                    'mautic.yellowbox_crm.sync.data_exchange_contacts',
                    'mautic.yellowbox_crm.sync.data_exchange_leads',
                    'mautic.yellowbox_crm.sync.data_exchange_accounts',
                    'mautic.integrations.sync.notification.handler_contact',
                ],
            ],

            'mautic.yellowbox_crm.sync.data_exchange_contacts'        => [
                'class'     => \MauticPlugin\MauticYellowboxCrmBundle\Sync\ContactDataExchange::class,
                'arguments' => [
                    'mautic.yellowbox_crm.settings',
                    'mautic.yellowbox_crm.value_normalizer',
                    'mautic.yellowbox_crm.repository.contacts',
                    'mautic.yellowbox_crm.validator.contact',
                    'mautic.integrations.helper.sync_mapping',
                    'mautic.yellowbox_crm.mapping.field_mapping',
                    'mautic.yellowbox_crm.modelFactory',
                    'mautic.integrations.sync.notification.handler_contact',
                ],
            ],
            'mautic.yellowbox_crm.sync.data_exchange_leads'           => [
                'class'     => \MauticPlugin\MauticYellowboxCrmBundle\Sync\LeadDataExchange::class,
                'arguments' => [
                    'mautic.yellowbox_crm.settings',
                    'mautic.yellowbox_crm.value_normalizer',
                    'mautic.yellowbox_crm.repository.leads',
                    'mautic.yellowbox_crm.validator.lead',
                    'mautic.yellowbox_crm.modelFactory',
                    'mautic.integrations.sync.notification.handler_contact',
                ],
            ],
            'mautic.yellowbox_crm.sync.data_exchange_accounts'        => [
                'class'     => \MauticPlugin\MauticYellowboxCrmBundle\Sync\AccountDataExchange::class,
                'arguments' => [
                    'mautic.yellowbox_crm.settings',
                    'mautic.yellowbox_crm.value_normalizer',
                    'mautic.yellowbox_crm.repository.accounts',
                    'mautic.yellowbox_crm.validator.account',
                    'mautic.yellowbox_crm.modelFactory',
                    'mautic.integrations.sync.notification.handler_company',
                ],
            ],
            'mautic.yellowbox_crm.lead_event_supplier'                => [
                'class'     => \MauticPlugin\MauticYellowboxCrmBundle\Service\LeadEventSupplier::class,
                'arguments' => ['mautic.lead.model.lead', 'doctrine.orm.entity_manager'],
            ],
            'mautic.yellowbox_crm.sync.events_service'                => [
                'class'     => \MauticPlugin\MauticYellowboxCrmBundle\Sync\EventSyncService::class,
                'arguments' => ['mautic.yellowbox_crm.lead_event_supplier', 'mautic.yellowbox_crm.repository.events', 'mautic.yellowbox_crm.settings'],
            ],
            'mautic.yellowbox_crm.modelFactory'                => [
                'class'     => \MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository\Mapping\ModelFactory::class,
                'arguments' => [],
            ],
            'mautic.yellowbox_crm.fieldDirectionFactory' => [
                'class'     => \MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository\Direction\FieldDirectionFactory::class,
                'arguments' => [],
            ],
            'mautic.yellowbox_crm.helper.ownerMauticSync'            => [
                'class'     => \MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Helper\OwnerMauticSync::class,
                'arguments' => [
                    'mautic.yellowbox_crm.repository.users',
                    'mautic.user.model.user',
                    'mautic.validator.email',
                    'security.encoder_factory',
                    'doctrine.orm.entity_manager',
                    'mautic.yellowbox_crm.settings',
                ],
            ],
        ],
        'models'       => [
        ],
        'integrations' => [
            'mautic.integration.yellowboxcrm'      => [
                'class'     => \MauticPlugin\MauticYellowboxCrmBundle\Integration\YellowboxCrmIntegration::class,
                'tags'      => [
                    'mautic.integration',
                    'mautic.basic_integration',
                ],
            ],
            'mautic.integration.yellowbox_crm.sync' => [
                'class'     => \MauticPlugin\MauticYellowboxCrmBundle\Integration\Provider\YellowboxSyncProvider::class,
                'tag'       => 'mautic.sync_integration',
                'arguments' => [
                    'mautic.yellowbox_crm.sync.data_exchange',
                ],
            ],
            'mautic.integration.yellowbox_crm.config' => [
                'class'     => \MauticPlugin\MauticYellowboxCrmBundle\Integration\Provider\YellowboxConfigProvider::class,
                'tag'       => 'mautic.config_integration',
                'arguments' => [
                    'mautic.yellowbox_crm.mapping.field_mapping',
                    'mautic.helper.core_parameters',
                ],
            ],
        ],
    ],
    'routes'      => [
        'main'   => [
        ],
        'public' => [
        ],
        'api'    => [
        ],
    ],
    'menu'        => [
    ],
    'parameters'  => [
        'yellowboxAvailableObjects' => [
            'Leads', 'Contacts', 'Accounts',
        ],
    ],
];
