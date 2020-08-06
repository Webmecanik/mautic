<?php

namespace MauticPlugin\AutomationBundle\Security\Authenticator;

use Doctrine\Common\Persistence\ObjectManager;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\KeycloakClient;
use KnpU\OAuth2ClientBundle\Security\Authenticator\SocialAuthenticator;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Model\UserModel;
use MauticPlugin\AutomationBundle\EventListener\SAMLSubscriber;
use MauticPlugin\AutomationBundle\Security\Authentication\KeycloakAuthenticationHandler;
use Psr\Log\LoggerInterface;
use Stevenmaguire\OAuth2\Client\Provider\KeycloakResourceOwner;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Translation\TranslatorInterface;

class KeycloakAuthenticator extends SocialAuthenticator
{
    private $clientRegistry;
    private $om;
    private $logger;
    private $httpUtils;
    private $userModel;
    private $encoder;
    private $kernel;
    private $translator;

    public function __construct(ClientRegistry $clientRegistry,
                                ObjectManager $om,
                                LoggerInterface $logger,
                                HttpUtils $httpUtils,
                                UserModel $userModel,
                                EncoderFactoryInterface $encoder,
                                HttpKernelInterface $kernel,
                                TranslatorInterface $translator)
    {
        $this->clientRegistry        = $clientRegistry;
        $this->om                    = $om;
        $this->logger                = $logger;
        $this->httpUtils             = $httpUtils;
        $this->userModel             = $userModel;
        $this->encoder               = $encoder;
        $this->kernel                = $kernel;
        $this->translator            = $translator;
    }

    public function getCredentials(Request $request)
    {
        if ('connect_keycloak_check' !== $request->attributes->get('_route')) {
            return null;
        }

        $this->logger->info('login launching');
        $token = $this->fetchAccessToken($this->getKeycloakClient());
        $request->getSession()->set('keycloak-token', $token);

        return $token;
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        /** @var KeycloakResourceOwner $keycloakUser */
        $keycloakUser = $this->getKeycloakClient()
            ->fetchUserFromToken($credentials);

        // 0) is there any right to come here?
        try {
            $this->getKeycloakClient()->getOAuth2Provider()->getAuthorizationToken($credentials);
        } catch (\Exception $e) {
            $this->logger->error('Keycloak ID: '.$keycloakUser->getId().', login failed ('.$e->getMessage().')');

            return null;
        }

        $email = $keycloakUser->getEmail();

        // 1) have they logged in with Keycloak before? Easy!
//        $existingUser = $this->om->getRepository(User::class)
//            ->findOneBy(['keycloakId' => $keycloakUser->getId()]);
//        if ($existingUser) {
//            return $existingUser;
//        }

        // 2) do we have a matching user by email?
        $user = $this->om->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        // 3) so create a new user!
        if (!$user) {
            $user = new User();
            $user->setRole($this->om->getReference('MauticUserBundle:Role', 1)); // Role unique à créer
            $user->setDateAdded(date('Y-m-d H:i:s'));
        }

        $user->setEmail($email);
        $user->setUsername($email);
        $user->setPassword($this->userModel->checkNewPassword($user, $this->encoder->getEncoder($user), EncryptionHelper::generateKey()));

        $names = explode(' ', $keycloakUser->getName());
        if (count($names) > 1) {
            $firstname = $names[0];
            $lastname  = $names[1];
        } else {
            $firstname = $lastname = $names[0];
        }
        $user->setFirstName($firstname);
        $user->setLastName($lastname);
        $this->userModel->saveEntity($user);

        // 4) and "register" them
//        $user->setKeycloakId($keycloakUser->getId());

        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        return true;
    }

    /**
     * @return KeycloakClient
     */
    private function getKeycloakClient()
    {
        return $this->clientRegistry->getClient('keycloak'); // "keycloak" is the key used in config/packages/knpu_oauth2_client.yaml
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $this->logger->info(sprintf('User "%s" has been authenticated successfully', $token->getUsername()));

        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $this->logger->info(sprintf('Authentication request failed: %s', $exception->getMessage()));

        $request->getSession()->remove('keycloak-token');

        $error = new AccessDeniedHttpException(
            $this->translator->trans('mautic.core.error.403',
                [
                    '%url%' => $request->getRequestUri(),
                ]
            ),
            null,
            403
        );
        $response = SAMLSubscriber::generateErrorResponse($request, $this->kernel, $error);

        if (!$response instanceof Response) {
            throw new \RuntimeException('Authentication Failure Handler did not return a Response.');
        }

        return $response;
    }

    /**
     * Called when authentication is needed, but it's not sent.
     * This redirects to the 'login'.
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $loginUrl = $this->httpUtils->generateUri($request, 'connect_keycloak_start');

        return SAMLSubscriber::generateUrlResponse($request, $loginUrl);
    }
}
