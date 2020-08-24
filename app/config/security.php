<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$firewalls = [
    'install' => [
        'pattern'   => '^/installer',
        'anonymous' => true,
        'context'   => 'mautic',
        'security'  => false,
    ],
    'dev' => [
        'pattern'   => '^/(_(profiler|wdt)|css|images|js)/',
        'security'  => true,
        'anonymous' => true,
    ],
    'login' => [
        'pattern'   => '^/s/login$',
        'anonymous' => true,
        'context'   => 'mautic',
    ],
    'sso_login' => [
        'pattern'            => '^/s/sso_login',
        'anonymous'          => true,
        'mautic_plugin_auth' => true,
        'context'            => 'mautic',
    ],
    'saml_login' => [
        'pattern'   => '^/s/saml/login$',
        'anonymous' => true,
        'context'   => 'mautic',
    ],
    'saml_discovery' => [
        'pattern'   => '^/saml/discovery$',
        'anonymous' => true,
        'context'   => 'mautic',
    ],
    'oauth2_token' => [
        'pattern'  => '^/oauth/v2/token',
        'security' => false,
    ],
    'oauth2_area' => [
        'pattern'    => '^/oauth/v2/authorize',
        'form_login' => [
            'provider'   => 'user_provider',
            'check_path' => '/oauth/v2/authorize_login_check',
            'login_path' => '/oauth/v2/authorize_login',
        ],
        'anonymous' => true,
    ],
    'oauth1_request_token' => [
        'pattern'  => '^/oauth/v1/request_token',
        'security' => false,
    ],
    'oauth1_access_token' => [
        'pattern'  => '^/oauth/v1/access_token',
        'security' => false,
    ],
    'oauth1_area' => [
        'pattern'    => '^/oauth/v1/authorize',
        'form_login' => [
            'provider'   => 'user_provider',
            'check_path' => '/oauth/v1/authorize_login_check',
            'login_path' => '/oauth/v1/authorize_login',
        ],
        'anonymous' => true,
    ],
    'api' => [
        'pattern'            => '^/api',
        'fos_oauth'          => true,
        'bazinga_oauth'      => true,
        'mautic_plugin_auth' => true,
        'stateless'          => true,
        'http_basic'         => true,
    ],
    'main' => [
        'pattern'       => '^/s/',
        'light_saml_sp' => [
            'provider'        => 'user_provider',
            'success_handler' => 'mautic.security.authentication_handler',
            'failure_handler' => 'mautic.security.authentication_handler',
            'user_creator'    => 'mautic.security.saml.user_creator',
            'username_mapper' => 'mautic.security.saml.username_mapper',

            // Environment variables will overwrite these with the standard login URLs if SAML is disabled
            'login_path'      => '%env(MAUTIC_SAML_LOGIN_PATH)%', // '/s/saml/login',,
            'check_path'      => '%env(MAUTIC_SAML_LOGIN_CHECK_PATH)%', // '/s/saml/login_check',
        ],
        'simple_form' => [
            'authenticator'        => 'mautic.user.form_authenticator',
            'csrf_token_generator' => 'security.csrf.token_manager',
            'success_handler'      => 'mautic.security.authentication_handler',
            'failure_handler'      => 'mautic.security.authentication_handler',
            'login_path'           => '/s/login',
            'check_path'           => '/s/login_check',
        ],
        'logout' => [
            'handlers' => [
                'mautic.security.logout_handler',
            ],
            'path'   => '/s/logout',
            'target' => '/s/login',
        ],
        'remember_me' => [
            'secret'   => '%mautic.rememberme_key%',
            'lifetime' => (int) $container->getParameter('mautic.rememberme_lifetime'),
            'path'     => '%mautic.rememberme_path%',
            'domain'   => '%mautic.rememberme_domain%',
        ],
        'fos_oauth'     => true,
        'bazinga_oauth' => true,
        'context'       => 'mautic',
    ],
    'public' => [
        'pattern'   => '^/',
        'anonymous' => true,
        'context'   => 'mautic',
    ],
];

if (!$container->getParameter('mautic.famework.csrf_protection')) {
    unset($firewalls['main']['simple_form']['csrf_token_generator']);
}

unset($firewalls['login']);
unset($firewalls['main']['simple_form']);

$firewalls['main']['remember_me'] = false;
$firewalls['main']['entry_point'] = 'mautic.security.authenticator.keycloak';

// If oauth2_area enabled, then rewrite settings
$firewalls['oauth2_area']   = [
    'pattern'   => '^/oauth/v2/authorize',
    'guard'     => [
        'provider'       => 'user_provider',
        'authenticators' => [
            'mautic.security.authenticator.keycloak',
        ],
    ],
    'anonymous' => true,
    'context'   => 'mautic',
];
$firewalls['oauth1_area']   = [
    'pattern'   => '^/oauth/v1/authorize',
    'guard'     => [
        'provider'       => 'user_provider',
        'authenticators' => [
            'mautic.security.authenticator.keycloak',
        ],
    ],
    'anonymous' => true,
    'context'   => 'mautic',
];
$firewalls['main']['guard'] = [
    'provider'       => 'user_provider',
    'authenticators' => [
        'mautic.security.authenticator.keycloak',
    ],
];

$firewalls['main']['logout'] = [
    'success_handler' => 'mautic.security.logout_success_handler',
    'path'            => '/s/logout',
    'target'          => '%mautic.after_logout_target%',
];

$container->loadFromExtension(
    'security',
    [
        'providers' => [
            'user_provider' => [
                'id' => 'mautic.user.provider',
            ],
        ],
        'encoders' => [
            'Symfony\Component\Security\Core\User\User' => [
                'algorithm'  => 'bcrypt',
                'iterations' => 12,
            ],
            'Mautic\UserBundle\Entity\User' => [
                'algorithm'  => 'bcrypt',
                'iterations' => 12,
            ],
        ],
        'role_hierarchy' => [
            'ROLE_ADMIN' => 'ROLE_USER',
        ],
        'firewalls'      => $firewalls,
        'access_control' => [
            ['path' => '^/api', 'roles' => 'IS_AUTHENTICATED_FULLY'],
            ['path' => '^/efconnect', 'roles' => 'IS_AUTHENTICATED_FULLY'],
            ['path' => '^/elfinder', 'roles' => 'IS_AUTHENTICATED_FULLY'],
        ],
    ]
);

$container->setParameter('mautic.saml_idp_entity_id', '%env(MAUTIC_SAML_ENTITY_ID)%');
$container->loadFromExtension(
    'light_saml_symfony_bridge',
    [
        'own' => [
            'entity_id' => '%mautic.saml_idp_entity_id%',
        ],
        'store' => [
            'id_state' => 'mautic.security.saml.id_store',
        ],
    ]
);

$this->import('security_api.php');

// List config keys we do not want the user to change via the config UI
$restrictedConfigFields = [
    'db_driver',
    'db_host',
    'db_table_prefix',
    'db_name',
    'db_user',
    'db_password',
    'db_path',
    'db_port',
    'secret_key',
];

// List config keys that are dev mode only
if ('prod' == $container->getParameter('kernel.environment')) {
    $restrictedConfigFields = array_merge($restrictedConfigFields, ['transifex_username', 'transifex_password']);
}

$container->setParameter('mautic.security.restrictedConfigFields', $restrictedConfigFields);
$container->setParameter('mautic.security.restrictedConfigFields.displayMode', \Mautic\ConfigBundle\Form\Helper\RestrictionHelper::MODE_REMOVE);

/*
 * Optional security parameters
 * mautic.security.disableUpdates = disables remote checks for updates
 * mautic.security.restrictedConfigFields.displayMode = accepts either remove or mask; mask will disable the input with a "Set by system" message
 */
$container->setParameter('mautic.security.disableUpdates', false);
