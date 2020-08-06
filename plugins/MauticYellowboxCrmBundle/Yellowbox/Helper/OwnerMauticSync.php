<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Helper;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\ArrayHelper;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\EmailBundle\Exception\InvalidEmailException;
use Mautic\EmailBundle\Helper\EmailValidator;
use Mautic\UserBundle\Model\UserModel;
use MauticPlugin\MauticYellowboxCrmBundle\Integration\Provider\YellowboxSettingProvider;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\User;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository\UserRepository;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;

class OwnerMauticSync
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var UserModel
     */
    private $userModel;

    /**
     * @var EmailValidator
     */
    private $emailValidator;

    /**
     * @var EncoderFactory
     */
    private $encoderFactory;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var YellowboxSettingProvider
     */
    private $settingProvider;

    /**
     * OwnerMauticSync constructor.
     */
    public function __construct(UserRepository $userRepository, UserModel $userModel, EmailValidator $emailValidator, EncoderFactory $encoderFactory, EntityManager $entityManager, YellowboxSettingProvider $settingProvider)
    {
        $this->userRepository  = $userRepository;
        $this->userModel       = $userModel;
        $this->emailValidator  = $emailValidator;
        $this->encoderFactory  = $encoderFactory;
        $this->entityManager   = $entityManager;
        $this->settingProvider = $settingProvider;
    }

    /**
     * @param string $idUser
     *
     * @return \Mautic\UserBundle\Entity\User|object|null
     *
     * @throws InvalidEmailException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidQueryArgumentException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\YellowboxPluginException
     */
    public function findOrCreateMauticUser($idUser)
    {
        $userModel = $this->userRepository->findOneBy(['id' => $idUser]);

        $emailAddress = ArrayHelper::getValue('email', $userModel->getData());
        $this->emailValidator->validate($emailAddress);
        $mauticUser = $this->userModel->getRepository()->findOneBy(['email'=>$emailAddress]);
        if (!$mauticUser) {
            $mauticUser = $this->createUser($userModel);
        }

        return $mauticUser;
    }

    /**
     * @return \Mautic\UserBundle\Entity\User
     *
     * @throws \Doctrine\ORM\ORMException
     */
    private function createUser(User $userModel)
    {
        $data = $userModel->getData();

        $user = new \Mautic\UserBundle\Entity\User();
        /** @var \Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface $encoder */
        $encoder = $this->encoderFactory->getEncoder($user);

        $emailAddress = ArrayHelper::getValue('email', $data);
        $user->setFirstName(ArrayHelper::getValue('givenName', $data));
        $user->setLastName(ArrayHelper::getValue('name', $data));
        $user->setUsername($this->getEmailNameFromAddress($emailAddress));
        $user->setEmail($emailAddress);
        $user->setPassword($encoder->encodePassword(EncryptionHelper::generateKey(), $user->getSalt()));
        $user->setRole($this->entityManager->getReference('MauticUserBundle:Role', $this->settingProvider->getMauticOwnerUserRole()));

        $this->entityManager->persist($user);
        $this->entityManager->flush($user);

        return $user;
    }

    /**
     * @param $email
     *
     * @return mixed
     */
    private function getEmailNameFromAddress($email)
    {
        list($name, $address) = explode('@', $email);

        return $name;
    }
}
