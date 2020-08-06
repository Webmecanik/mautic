<?php

namespace MauticPlugin\AutomationBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractFormController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class KeycloakController extends AbstractFormController
{
    /**
     * Link to this controller to start the "connect" process.
     */
    public function connectAction(Request $request)
    {
        $clientRegistry = $this->container->get('knpu.oauth2.registry');
        $session        = $request->getSession();

        // https://github.com/FriendsOfSymfony/FOSOAuthServerBundle/blob/master/Resources/doc/a_note_about_security.md
        if ($session->has('_security.oauth2_area.target_path')) {
            if (false !== strpos($session->get('_security.oauth2_area.target_path'), $this->generateUrl('fos_oauth_server_authorize'))) {
                $session->set('_fos_oauth_server.ensure_logout', true);
            }
        }

        // will redirect to Keycloak!
        return $clientRegistry
            ->getClient('keycloak') // key used in config/packages/knpu_oauth2_client.yaml
            ->redirect([
                'openid profile email', // the scopes you want to access
            ], ['kc_locale' => $this->get('mautic.helper.core_parameters')->getParameter('kc_client_locale')])
            ;
    }

    /**
     * After going to Keycloak, you're redirected back here
     * because this is the "redirect_route" you configured
     * in config/config.php.
     */
    public function connectCheckAction(Request $request)
    {
        $token   = $this->get('security.token_storage')->getToken();
        $handler = $this->get('mautic.security.keycloak_authentication_handler');

        $response = $handler->onAuthenticationSuccess($request, $token);

        if (!$response instanceof Response) {
            throw new \RuntimeException('Authentication Success Handler did not return a Response.');
        }

        return $response;
    }

    /**
     * URL for updownIO check.
     */
    public function updownCheckAction(Request $request)
    {
        return new Response('<p>Ok Updown !</p>', 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }
}
