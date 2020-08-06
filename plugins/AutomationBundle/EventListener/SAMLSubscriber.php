<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\AutomationBundle\EventListener;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;
use Symfony\Component\Translation\TranslatorInterface;

class SAMLSubscriber implements EventSubscriberInterface
{
    protected $tokenStorage;
    protected $authChecker;
    protected $clientRegistry;
    protected $router;
    protected $kernel;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    public function __construct(TokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authChecker,
        ClientRegistry $clientRegistry,
        UrlGeneratorInterface $router,
        HttpKernelInterface $kernel,
        TranslatorInterface $translator,
        CoreParametersHelper $coreParametersHelper
    ) {
        $this->tokenStorage         = $tokenStorage;
        $this->authChecker          = $authChecker;
        $this->clientRegistry       = $clientRegistry;
        $this->router               = $router;
        $this->kernel               = $kernel;
        $this->translator           = $translator;
        $this->coreParametersHelper = $coreParametersHelper;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    public function notFound($msg = 'mautic.core.url.error.404')
    {
        return $this->renderException(
            new NotFoundHttpException(
                $this->translator->trans($msg,
                    [
                        '%url%' => $this->request->getRequestUri(),
                    ]
                )
            )
        );
    }

    private function blockPages(Request $request = null)
    {
        if ($request) {
            $route        = $request->attributes->get('_route');
            $objectAction = $request->attributes->get('objectAction');
            switch ($route) {
                case 'mautic_user_action':
                case 'mautic_user_logincheck':
                case 'mautic_api_useradd':
                case 'mautic_user_passwordreset':
                case 'mautic_user_passwordresetconfirm':
                    if ('mautic_user_action' === $route && 'new' === $objectAction) {
                        throw new AccessDeniedHttpException($this->translator->trans('mautic.core.url.error.401', ['%url%' => $request->getRequestUri()]));
                    }
                    break;
            }
        }
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $this->blockPages($event->getRequest());

        if ($event->isMasterRequest()) {
            /** @var Request $request */
            $request = $event->getRequest();
            $route   = $request->attributes->get('_route');

            if (false !== strpos($route, 'lightsaml') && empty($this->coreParametersHelper->get('saml_idp_metadata'))) {
                throw new NotFoundHttpException();
            }

            // Si on est connecté et que le Token utilisé est celui du Guard Symfony
            // If you are connected and the Token used is that of the Guard Symfony
            if ($this->isUserLogged() && PostAuthenticationGuardToken::class === get_class($this->tokenStorage->getToken())) {
                $session     = $request->getSession();
                $accessToken = $session->get('keycloak-token');
                $provider    = $this->clientRegistry->getClient('keycloak')->getOAuth2Provider();
                $response    = null;

                // Si pas de access token
                if (!$accessToken) {
                    $response = $this->redirectFailedLogin($request);
                }

                // Si access token expiré
                if (!$response && $accessToken->hasExpired()) {
                    try {
                        $accessToken = $provider->getRefreshToken($accessToken);
                    } catch (\Exception $e) {
                        $response = $this->redirectFailedLogin($request);
                    }

                    // Si token renouvelé on l'enregistre en session
                    if (!$response) {
                        $session->set('keycloak-token', $accessToken);
                    }
                }

                // 403 if not authorized by SSO
                if (!$response) {
                    try {
                        $provider->getAuthorizationToken($accessToken);
                    } catch (\Exception $e) {
                        if (403 === $e->getCode()) {
                            $request->getSession()->invalidate();
                            $this->tokenStorage->setToken(null);

                            $error = new AccessDeniedHttpException(
                                $this->translator->trans('mautic.core.error.403',
                                    [
                                        '%url%' => $request->getRequestUri(),
                                    ]
                                ),
                                null,
                                403
                            );
                            $response = self::generateErrorResponse($request, $this->kernel, $error);
                        } else {
                            $response = $this->redirectFailedLogin($request);
                        }
                    }
                }
                if ($response) {
                    $event->setResponse($response);
                }
            }
        }
    }

    private function isUserLogged()
    {
        return null !== $this->tokenStorage->getToken() && ($this->authChecker->isGranted('IS_AUTHENTICATED_REMEMBERED') || $this->authChecker->isGranted('IS_AUTHENTICATED_FULLY'));
    }

    // Gestion de la redirection en cas d'erreur en fonction d'où on vient
    private function redirectFailedLogin($request)
    {
        $session   = $request->getSession();
        $route     = $request->attributes->get('_route');
        $logoutUrl = $this->router->generate('mautic_user_logout');

        // Si on vient de la route pour obtenir un Oauth2/Oauth1, redirection vers login
        if ('fos_oauth_server_authorize' == $route
            || 'bazinga_oauth_server_authorize' == $route
            || 'bazinga_oauth_login_allow' == $route) {
            $session->invalidate();
            $this->tokenStorage->setToken(null);

            $response = self::generateUrlResponse($request, $this->router->generate($route, $request->query->all(), UrlGeneratorInterface::ABSOLUTE_URL));
        } else { // Sinon redirection vers logout
            $response = self::generateUrlResponse($request, $logoutUrl);
        }

        return $response;
    }

    /*
     * Création d'une erreur au format Simple ou Ajax
     */
    public static function generateErrorResponse(Request $request, HttpKernelInterface $kernel, \Exception $e)
    {
        defined('MAUTIC_AJAX_VIEW') || define('MAUTIC_AJAX_VIEW', 1);

        $data       = [];
        $exception  = FlattenException::create($e, $e->getCode(), $request->headers->all());
        $parameters = ['request' => $request, 'exception' => $exception, '_controller' => 'MauticCoreBundle:Exception:show'];
        $query      = ['ignoreAjax' => true, 'request' => $request, 'subrequest' => true];

        $subRequest = $request->duplicate($query, null, $parameters);
        $response   = $kernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);

        if ($request->isXmlHttpRequest()) {
            if ($response instanceof RedirectResponse) {
                $data['redirect'] = $response->getTargetUrl();
                $data['route']    = false;
            } else {
                $data['newContent'] = $response->getContent();
            }

            return new JsonResponse($data, $e->getCode());
        } else {
            return $response;
        }
    }

    /*
     * Gestion de la réponse pour une requête Simple ou Ajax
     */
    public static function generateUrlResponse(Request $request, $url)
    {
        if ($request->isXmlHttpRequest()) {
            $data['redirect'] = $url;
            $data['route']    = false;
            $response         = new JsonResponse($data, Response::HTTP_CREATED); // Réponse en 201 car le 200 et 307 renvoi une 500
        } else {
            $response = new RedirectResponse($url, Response::HTTP_TEMPORARY_REDIRECT);
        }

        return $response;
    }
}
