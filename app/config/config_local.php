<?php

$configParameterBag = (new \Mautic\CoreBundle\Loader\ParameterLoader())->getParameterBag();

Swift_Preferences::getInstance()->setCacheType('array');

//Knpu oauth2 configuration
$isTestportal     = false !== strpos((string) $configParameterBag->get('portal_url'), 'testportail.webmecanik.com');
$kc_client_id     = $configParameterBag->get('kc_client_id');
$kc_client_secret = $configParameterBag->get('kc_client_secret');
$container->loadFromExtension('knpu_oauth2_client', [
    'clients' => [
        'keycloak' => [
            'type'            => 'keycloak',
            'client_id'       => $kc_client_id,
            'client_secret'   => $kc_client_secret,
            'redirect_route'  => 'connect_keycloak_check',
            'redirect_params' => [],
            'auth_server_url' => $isTestportal ? 'https://testsso.webmecanik.com/auth' : 'https://ssoauthentification.com/auth',
            'realm'           => 'WMK',
            // Optional: Encryption algorith, i.e. RS256
            // encryption_algorithm: ''
            // Optional: Encryption key path, i.e. ../key.pem
            // encryption_key_path: ''
            // Optional: Encryption key, i.e. contents of key or certificate
            // encryption_key: ''
            // whether to check OAuth2 "state": defaults to true
            'use_state' => true,
        ],
    ],
]);
