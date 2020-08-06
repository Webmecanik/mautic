<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticOwnerManagerBundle\Helper;

use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Model\UserModel;
use MauticPlugin\MauticOwnerManagerBundle\Entity\OwnerManager;
use MauticPlugin\MauticOwnerManagerBundle\Model\OwnerManagerModel;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class EventHelper.
 */
class TimelineEventHelper
{
    /**
     * @var array|\Doctrine\ORM\Tools\Pagination\Paginator
     */
    private $users;

    /**
     * @var array|\Doctrine\ORM\Tools\Pagination\Paginator
     */
    private $ownerManagers;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * TimelineEventHelper constructor.
     */
    public function __construct(
        UserModel $userModel,
        OwnerManagerModel $ownerManagerModel,
        TranslatorInterface $translator,
        RouterInterface $router
    ) {
        $this->users = $users = $userModel->getEntities(
            [
                'ignore_paginator' => true,
            ]
        );

        $this->ownerManagers = $ownerManagerModel->getEntities(
            [
                'ignore_paginator' => true,
            ]
        );
        $this->translator    = $translator;
        $this->router        = $router;
    }

    /**
     * @param $ownerManagerId
     * @param $ownerId
     *
     * @return string
     */
    public function getLabel($ownerManagerId, $ownerId)
    {
        return $this->translator->trans(
            'mautic.ownermanager.event.label',
            [
                '%actionUrl%' => $this->router->generate(
                    'mautic_ownermanager_action',
                    [
                        'objectId'       => $ownerManagerId,
                        'objectAction'   => 'edit',
                    ]
                ),
                '%action%'    => $this->getActionName($ownerManagerId),
                '%owner%'     => $this->getOwnerName($ownerId),
            ]
        );
    }

    /**
     * @param $ownerId
     *
     * @return string
     */
    private function getOwnerName($ownerId)
    {
        /** @var User $user */
        foreach ($this->users as $user) {
            if ($ownerId == $user->getId()) {
                return sprintf('%s %s (%s)', $user->getFirstName(), $user->getLastName(), $user->getId());
            }
        }

        return '';
    }

    /**
     * @param $ownerManagerId
     *
     * @return string
     */
    private function getActionName($ownerManagerId)
    {
        /** @var OwnerManager $ownerManager */
        foreach ($this->ownerManagers as $ownerManager) {
            if ($ownerManagerId == $ownerManager->getId()) {
                return sprintf('%s (%s)', $ownerManager->getName(), $ownerManager->getId());
            }
        }
    }
}
