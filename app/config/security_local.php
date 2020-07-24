<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

// load basic Mautic security
include_once 'security.php';

// login/logout SSO changes
unset($firewalls['login']);
unset($firewalls['main']['simple_form']);
unset($firewalls['main']['remember_me']);
$firewalls['main']['remember_me'] = false;

$firewalls['main']['entry_point'] = 'mautic.security.authenticator.keycloak';

// If oauth2_area enabled, then rewrite settings
if (isset($firewalls['oauth2_area'])) {
    $firewalls['oauth2_area'] = [
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
}
if (isset($firewalls['oauth1_area'])) {
    $firewalls['oauth1_area'] = [
        'pattern' => '^/oauth/v1/authorize',
        'guard'   => [
            'provider'       => 'user_provider',
            'authenticators' => [
                'mautic.security.authenticator.keycloak',
            ],
        ],
        'anonymous' => true,
        'context'   => 'mautic',
    ];
}
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

$container->loadFromExtension('security',
    [
        'firewalls' => $firewalls,
    ]
);

// disable upgrade notification
$container->setParameter('mautic.security.disableUpdates', true);

// hide Identify visitor by device fingerprint and Identify visitors by IP options
$myCutomRestrictedConfigFields = [
    'site_url',
    'webroot',
    'update_stability',
    'cache_path',
    'log_path',
    'image_path',
    'cached_data_timeout',
    'trusted_hosts',
    'trusted_proxies',
    'cors_restrict_domains',
    'max_entity_lock_time',
    'track_by_tracking_url',
    'anonymize_ip',
    'track_contact_by_ip',
    'queue_protocol',
    'upload_dir',
    'max_size',
    'api_enable_basic_auth',
    'cors_restrict_domains',
    'ip_lookup_service',
    'ip_lookup_auth',
    'ip_lookup_create_organization',
    'ip_lookup_config',
    'mailer_spool_type',
    'mailer_spool_path',
    'mailer_spool_msg_limit',
    'mailer_spool_time_limit',
    'mailer_spool_recover_timeout',
    'mailer_spool_recover_timeout',
    'mailer_spool_clear_timeout',
    'queue_mode',
    'events_orderby_dir',
    'saml_idp_metadata',
    'saml_idp_default_role',
    'saml_idp_email_attribute',
    'saml_idp_username_attribute',
    'saml_idp_firstname_attribute',
    'saml_idp_lastname_attribute',
    'saml_idp_own_certificate',
    'saml_idp_own_private_key',
    'saml_idp_own_password',
];

if (!isset($restrictedConfigFields)) {
    $restrictedConfigFields = [];
}

$restrictedConfigFields = array_merge($restrictedConfigFields, $myCutomRestrictedConfigFields);
$container->setParameter('mautic.security.restrictedConfigFields', $restrictedConfigFields);
