<?php

declare(strict_types=1);

return [
    'name'        => 'Brick Builder',
    'description' => 'Exclusive builder for Automation',
    'version'     => '1.0.0',
    'author'      => 'Webmecanik',
    'routes'      => [
        'main'   => [
            'brickbuilder_builder' => [
                'path'       => '/brickbuilder/{objectType}/{objectId}',
                'controller' => 'BrickBuilderBundle:Brick:builder',
            ],
            'brickbuilder_autosave' => [
                'path'       => '/brickbuilder/autosave/{objectType}/{objectId}',
                'controller' => 'BrickBuilderBundle:Ajax:autosave',
            ],
        ],
        'public' => [],
        'api'    => [],
    ],
    'menu'        => [],
    'services'    => [
        'other'        => [
            // Provides access to configured API keys, settings, field mapping, etc
            'brickbuilder.service.autosave' => [
                'class'     => \MauticPlugin\BrickBuilderBundle\AutoSave\AutoSaveAbstract::class,
                'arguments' => [
                    'mautic.helper.cache_storage',
                    'mautic.helper.user',
                ],
            ],
            'brickbuilder.service.autosave.email' => [
                'class'     => \MauticPlugin\BrickBuilderBundle\AutoSave\AutoSaveEmail::class,
                'arguments' => [
                    'mautic.helper.cache_storage',
                    'mautic.helper.user',
                ],
            ],

            'brickbuilder.helper.mjml' => [
                'class'     => \MauticPlugin\BrickBuilderBundle\Helper\MjmlHelper::class,
                'arguments' => [
                    'mautic.helper.templating',
                    'mautic.helper.theme',
                    'mautic.helper.core_parameters',
                ],
            ],
        ],
        'sync'         => [],
        'integrations' => [
        ],
        'models'  => [
            'brickbuilder.model' => [
                'class'     => \MauticPlugin\BrickBuilderBundle\Model\BrickBuilderModel::class,
                'arguments' => [
                    'request_stack',
                    'mautic.email.model.email',
                ],
            ],
        ],
        'helpers' => [
        ],
        'events'  => [
            'brickbuilder.event.assets.subscriber' => [
                'class'     => \MauticPlugin\BrickBuilderBundle\EventSubscriber\AssetsSubscriber::class,
                'arguments' => [
                    'mautic.email.model.email',
                    'brickbuilder.helper.mjml',
                    'mautic.helper.core_parameters',
                    'mautic.helper.user',
                    'mautic.helper.paths',
                ],
            ],
            'brickbuilder.event.email.subscriber' => [
                'class'     => \MauticPlugin\BrickBuilderBundle\EventSubscriber\EmailSubscriber::class,
                'arguments' => [
                    'brickbuilder.model',
                    'brickbuilder.service.autosave.email',
                    'request_stack',
                    'mautic.helper.core_parameters',
                ],
            ],
            'brickbuilder.event.content.subscriber' => [
                'class'     => \MauticPlugin\BrickBuilderBundle\EventSubscriber\InjectCustomContentSubscriber::class,
                'arguments' => [
                    'brickbuilder.model',
                    'mautic.helper.templating',
                    'request_stack',
                    'router',
                    'brickbuilder.service.autosave.email',
                    'mautic.helper.core_parameters',
                ],
            ],
        ],
    ],
    'parameters' => [
        \MauticPlugin\BrickBuilderBundle\Entity\BrickBuilder::BOTH_BUILDER_SUPPORT => false,
        \MauticPlugin\BrickBuilderBundle\Entity\BrickBuilder::BRICK_BUILDER_ENABLE => false,
    ],
];
