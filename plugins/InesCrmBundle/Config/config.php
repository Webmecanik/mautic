<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'name'        => 'Ines CRM',
    'description' => 'Ines CRM integration',
    'author'      => 'webmecanik.com',
    'version'     => '1.0.0',
    'services'    => [
        'events'  => [],
        'forms'   => [
        ],
        'helpers' => [],
        'other'   => [
        ],
        'models'       => [],
        'integrations' => [
            'mautic.integration.inescrm' => [
                'class'     => \MauticPlugin\InesCrmBundle\Integration\InesCRMIntegration::class,
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
    'routes'     => [],
    'menu'       => [
    ],
    'parameters' => [
    ],
];
