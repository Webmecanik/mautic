<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'routes' => [
        'main' => [
            'mautic_ownermanager_index'  => [
                'path'       => '/ownermanager/{page}',
                'controller' => 'MauticOwnerManagerBundle:OwnerManager:index',
            ],
            'mautic_ownermanager_action' => [
                'path'       => '/ownermanager/{objectAction}/{objectId}',
                'controller' => 'MauticOwnerManagerBundle:OwnerManager:execute',
            ],
        ],
        'api'  => [
            'mautic_api_ownermanageractionsstandard' => [
                'standard_entity' => true,
                'name'            => 'ownermanager',
                'path'            => '/ownermanager',
                'controller'      => 'MauticOwnerManagerBundle:Api\OwnerManagerApi',
            ],
        ],
    ],
    'menu'   => [
        'main' => [
            'mautic.ownermanagers.menu.root' => [
                'id'        => 'mautic_ownermanagers_root',
                'iconClass' => 'fa-users',
                'access'    => ['ownermanager:ownermanager:view'],
                'priority'  => 30,
                'route'     => 'mautic_ownermanager_index',
                'checks'    => [
                    'integration' => [
                        'OwnerManager' => [
                            'enabled' => true,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'categories' => [
        'ownermanager' => null,
    ],

    'services' => [
        'events'       => [
            'mautic.ownermanager.ownermanager.subscriber' => [
                'class'     => \MauticPlugin\MauticOwnerManagerBundle\EventListener\OwnerManagerSubscriber::class,
                'arguments' => [
                    'mautic.ownermanager.model.ownermanager',
                    'request_stack',
                    'mautic.lead.model.lead',
                    'mautic.security',
                    'mautic.core.model.notification',
                    'mautic.user.model.user',
                    'translator',
                    'router',
                ],
            ],
            'mautic.ownermanager.lead.subscriber'         => [
                'class'     => \MauticPlugin\MauticOwnerManagerBundle\EventListener\LeadSubscriber::class,
                'arguments' => [
                    'mautic.ownermanager.model.ownermanager',
                    'mautic.ownermanager.helper.timeline_event',
                    'translator',
                    '@doctrine.orm.entity_manager',
                ],
            ],
            'mautic.ownermanager.page.subscriber'         => [
                'class'     => \MauticPlugin\MauticOwnerManagerBundle\EventListener\PageSubscriber::class,
                'arguments' => [
                    'mautic.ownermanager.model.ownermanager',
                ],
            ],
        ],
        'forms'        => [
            'mautic.ownermanager.type.form' => [
                'class'     => \MauticPlugin\MauticOwnerManagerBundle\Form\Type\OwnerManagerType::class,
                'arguments' => [
                    'mautic.security',
                    '@doctrine.orm.entity_manager',
                ],
            ],
        ],
        'models'       => [
            'mautic.ownermanager.model.ownermanager' => [
                'class'     => \MauticPlugin\MauticOwnerManagerBundle\Model\OwnerManagerModel::class,
                'arguments' => [
                    'session',
                    'mautic.helper.ip_lookup',
                    'mautic.lead.model.lead',
                    'mautic.ownermanager.integration.settings',
                ],
            ],
        ],
        'helpers'      => [
            'mautic.ownermanager.helper.timeline_event' => [
                'class'     => \MauticPlugin\MauticOwnerManagerBundle\Helper\TimelineEventHelper::class,
                'arguments' => [
                    'mautic.user.model.user',
                    'mautic.ownermanager.model.ownermanager',
                    'translator',
                    'router',
                ],
            ],
        ],
        'others'       => [
            'mautic.ownermanager.integration.settings' => [
                'class'     => \MauticPlugin\MauticOwnerManagerBundle\Integration\OwnerManagerSettings::class,
                'arguments' => [
                    'mautic.helper.integration',
                ],
            ],
        ],
        'integrations' => [
            'mautic.integration.ownermanager' => [
                'class'     => \MauticPlugin\MauticOwnerManagerBundle\Integration\OwnerManagerIntegration::class,
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
];
