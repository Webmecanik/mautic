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

// disable upgrade notification
$container->setParameter('mautic.security.disableUpdates', true);

// hide Identify visitor by device fingerprint and Identify visitors by IP options
$myCutomRestrictedConfigFields = [
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
];

$restrictedConfigFields = array_merge($restrictedConfigFields, $myCutomRestrictedConfigFields);
$container->setParameter('mautic.security.restrictedConfigFields', $restrictedConfigFields);
