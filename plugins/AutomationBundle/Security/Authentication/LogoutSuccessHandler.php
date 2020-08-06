<?php

namespace MauticPlugin\AutomationBundle\Security\Authentication;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\UserBundle\Event\LogoutEvent;
use Mautic\UserBundle\Model\UserModel;
use Mautic\UserBundle\UserEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;

class LogoutSuccessHandler implements LogoutSuccessHandlerInterface
{
    /**
     * @var UserModel
     */
    protected $userModel;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var \Mautic\UserBundle\Entity\User|null
     */
    protected $user;

    /** @var HttpUtils */
    private $httpUtils;

    /** @var CoreParametersHelper */
    private $params;

    /** @var ClientRegistry */
    private $clientRegistry;

    public function __construct(UserModel $userModel, EventDispatcherInterface $dispatcher, UserHelper $userHelper, HttpUtils $httpUtils, CoreParametersHelper $params, ClientRegistry $clientRegistry)
    {
        $this->userModel      = $userModel;
        $this->dispatcher     = $dispatcher;
        $this->user           = $userHelper->getUser();
        $this->httpUtils      = $httpUtils;
        $this->params         = $params;
        $this->clientRegistry = $clientRegistry;
    }

    public function onLogoutSuccess(Request $request)
    {
        $url = $this->httpUtils->generateUri($request, $this->params->get('after_logout_target'));

        if ($this->dispatcher->hasListeners(UserEvents::USER_LOGOUT)) {
            $event = new LogoutEvent($this->user, $request);
            $this->dispatcher->dispatch(UserEvents::USER_LOGOUT, $event);
        }

        // Clear session
        $session = $request->getSession();
        $session->clear();

        if (isset($event)) {
            $sessionItems = $event->getPostSessionItems();
            foreach ($sessionItems as $key => $value) {
                $session->set($key, $value);
            }
        }

        // Note that a logout occurred
        $session->set('post_logout', true);

        return new RedirectResponse($this->clientRegistry->getClient('keycloak')->getOAuth2Provider()->getLogoutUrl(['redirect_uri' => $url]));
    }
}
