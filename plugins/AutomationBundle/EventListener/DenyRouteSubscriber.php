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
use Mautic\CoreBundle\Helper\UserHelper;
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

class DenyRouteSubscriber implements EventSubscriberInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    private $userHelper;

    public function __construct(TranslatorInterface $translator, UserHelper $userHelper)
    {
        $this->translator = $translator;
        $this->userHelper = $userHelper;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 20],
        ];
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($request = $event->getRequest()) {
            $route        = $request->attributes->get('_route');
            $objectAction = $request->attributes->get('objectAction');
            switch ($route) {
                case 'mautic_user_action':
                    if (in_array($objectAction, ['new', 'delete'])) {
                        throw new AccessDeniedHttpException($this->translator->trans('mautic.core.url.error.401', ['%url%' => $request->getRequestUri()]));
                    }
                    break;
                case 'mautic_user_logincheck':
                case 'mautic_api_useradd':
                case 'mautic_user_passwordreset':
                case 'mautic_user_passwordresetconfirm':
                    throw new AccessDeniedHttpException($this->translator->trans('mautic.core.url.error.401', ['%url%' => $request->getRequestUri()]));
                    break;

                case 'mautic_sysinfo_index':
                    $user = $this->userHelper->getUser();
                    if (false !== strpos($user->getEmail(), '@webmecanik')) {
                        throw new AccessDeniedHttpException($this->translator->trans('mautic.core.url.error.401', ['%url%' => $request->getRequestUri()]));
                    }
                    break;
            }
        }
    }
}
