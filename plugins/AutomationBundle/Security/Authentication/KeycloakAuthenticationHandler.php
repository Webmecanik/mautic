<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\AutomationBundle\Security\Authentication;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class KeycloakAuthenticationHandler implements AuthenticationSuccessHandlerInterface, AuthenticationFailureHandlerInterface
{
    private $router;

    /**
     * Constructor.
     */
    public function __construct(RouterInterface $router, Session $session)
    {
        $this->router  = $router;
    }

    /**
     * onAuthenticationSuccess.
     *
     * @return Response
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        $session = $request->getSession();
        // Remove post_logout if set
        $session->remove('post_logout');

        $format = $request->request->get('format');

        if ('json' == $format) {
            $array    = ['success' => true];
            $response = new Response(json_encode($array));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        } else {
            $redirectUrl = $this->router->generate('mautic_dashboard_index');

            // Redirection quand on se connecte sur l'instance
            if ($session->has('_security.main.target_path')) {
                $redirectUrl = $session->get('_security.main.target_path');
            } elseif ($session->has('_security.oauth2_area.target_path')) { // Redirection quand on se connecte pour un Oauth2
                $redirectUrl = $session->get('_security.oauth2_area.target_path');
            } elseif ($session->has('_security.oauth1_area.target_path')) { // Redirection quand on se connecte pour un Oauth1
                $redirectUrl = $session->get('_security.oauth1_area.target_path');
            }

            return new RedirectResponse($redirectUrl);
        }
    }

    /**
     * onAuthenticationFailure.
     *
     * @return Response
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        // Remove post_logout if set
        $request->getSession()->remove('post_logout');

        $format = $request->request->get('format');

        if ('json' == $format) {
            $array    = ['success' => false, 'message' => $exception->getMessage()];
            $response = new Response(json_encode($array));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        } else {
            $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);

            return new RedirectResponse($this->router->generate('login'));
        }
    }
}
