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
    'name'        => 'Automation',
    'description' => 'Custom improvements for Automation',
    'author'      => 'webmecanik.com',
    'version'     => '1.0.0',
    'services'    => [
        'events'  => [
            'automation.saml.route.subscriber' => [
                'class'     => \MauticPlugin\AutomationBundle\EventListener\SAMLSubscriber::class,
                'arguments' => [
                    'security.token_storage',
                    'security.authorization_checker',
                    'knpu.oauth2.registry',
                    '@router',
                    'http_kernel',
                    'translator',
                    'mautic.helper.core_parameters',
                ],
            ],
            'automation.deny.route.subscriber' => [
                'class'     => \MauticPlugin\AutomationBundle\EventListener\DenyRouteSubscriber::class,
                'arguments' => [
                    'translator',
                ],
            ],
            'automation.subscriber.asset'      => [
                'class'     => \MauticPlugin\AutomationBundle\EventListener\AssetSubscriber::class,
                'arguments' => [
                    'request_stack',
                    'mautic.helper.core_parameters',
                ],
            ],
            'automation.subscriber.doctrine'      => [
                'class'     => \MauticPlugin\AutomationBundle\EventListener\DoctrineSubscriber::class,
                'tag'       => 'doctrine.event_subscriber',
            ],
        ],
        'forms'   => [
        ],
        'helpers' => [],
        'other'   => [
            'mautic.security.keycloak_authentication_handler' => [
                'class'     => \MauticPlugin\AutomationBundle\Security\Authentication\KeycloakAuthenticationHandler::class,
                'arguments' => [
                    'router',
                    'session',
                ],
            ],
            'mautic.security.logout_success_handler' => [
                'class'     => \MauticPlugin\AutomationBundle\Security\Authentication\LogoutSuccessHandler::class,
                'arguments' => [
                    'mautic.user.model.user',
                    'event_dispatcher',
                    'mautic.helper.user',
                    'security.http_utils',
                    'mautic.helper.core_parameters',
                    'knpu.oauth2.registry',
                ],
            ],
            'mautic.security.authenticator.keycloak' => [
                'class'     => \MauticPlugin\AutomationBundle\Security\Authenticator\KeycloakAuthenticator::class,
                'arguments' => [
                    'knpu.oauth2.registry',
                    'doctrine.orm.entity_manager',
                    'monolog.logger.mautic',
                    'security.http_utils',
                    'mautic.user.model.user',
                    'security.encoder_factory',
                    'http_kernel',
                    'translator',
                ],
            ],
        ],
        'models'       => [],
        'integrations' => [
        ],
    ],
    'routes' => [
        'main' => [
            'connect_keycloak_check' => [
                'path'       => '/connect/keycloak/check',
                'controller' => 'AutomationBundle:Keycloak:connectCheck',
            ],
        ],
        'public' => [
            'connect_keycloak_start' => [
                'path'       => '/connect/keycloak',
                'controller' => 'AutomationBundle:Keycloak:connect',
            ],
            'updown_check' => [
                'path'       => '/updown/check',
                'controller' => 'AutomationBundle:Keycloak:updownCheck',
            ],
        ],
        'api' => [
            'mautic_api_useradd' => [
                'path'       => '/users/add',
                'controller' => 'MauticUserBundle:Api\UserApi:newEntity',
                'method'     => 'POST',
            ],
        ],
    ],
    'menu' => [
        'admin' => [
            'mautic.portal.menu.index' => [
                'uri'            => '%mautic.portal_url%',
                'iconClass'      => 'fa-home',
                'id'             => 'mautic_portal_index',
                'linkAttributes' => [
                    'target' => '_blank',
                ],
            ],
        ],
    ],
    'parameters' => [
        'kc_client_id'        => 'localhost-Automation_dev',
        'kc_client_secret'    => 'f7e32c90-c25d-4b7f-86c0-d0692fac5f07',
        'kc_client_locale'    => 'en',
        'portal_url'          => 'https://testportail.webmecanik.com/dashboard',
        'after_logout_target' => '/connect/keycloak',

        'custom_sidebar_style'             => 'background-color: #ffffff',
        'custom_sidebar_link_style'        => 'background-color: #ffffff',
        'custom_logo_src'                  => 'media/images/atmt_img/automation_picto_mautic.png',
        'custom_logo_style'                => 'float: left',
        'custom_logo_text_src'             => 'media/images/atmt_img/automation_logo_mautic.png',
        'custom_logo_text_style'           => 'float: left',
        'custom_menu_style'                => '',
        'custom_login_logo_src'            => 'media/images/atmt_img/automation_logo_lb200.png',
        'custom_login_logo_wrapper_style'  => 'width:200px',
        'mail_error_support_manual_report' => 'support@webmecanik.com',
        'mail_error_support_mail_auto'     => 'support+bot@webmecanik.com',
        'mail_error_support_ignored_url'   => [
            '0' => 'apple-touch-icon',
            '1' => 'dnt-policy',
            '2' => 'browserconfig',
            '3' => 'apple-app-site-association',
        ],
        'custom_copyright_footer'            => '<a href="http://www.webmecanik.com">Webmecanik</a>',
        'custom_page_title'                  => 'Webmecanik Automation',
        'custom_favicon'                     => 'media/images/atmt_img/favicon.ico',
        'email_creation_show_bcc'            => false,
        'enable_custom_filter_on_sugar_sync' => false,
        'custom_exception_logo_src_200'      => 'media/images/atmt_img/automation_logo_lb200.png',
        'custom_exception_page_signature'    => 'Automation bot',
        'custom_exception_email_subject'     => 'Support request - Code',
    ],
];
