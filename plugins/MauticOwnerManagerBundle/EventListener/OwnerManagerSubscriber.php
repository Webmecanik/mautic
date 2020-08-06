<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticOwnerManagerBundle\EventListener;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Helper\ArrayHelper;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\UserBundle\Model\UserModel;
use MauticPlugin\MauticOwnerManagerBundle\Entity\OwnerManager;
use MauticPlugin\MauticOwnerManagerBundle\Event\OwnerManagerEvent;
use MauticPlugin\MauticOwnerManagerBundle\Model\OwnerManagerModel;
use MauticPlugin\MauticOwnerManagerBundle\OwnerManagerEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class OwnerManagerSubscriber implements EventSubscriberInterface
{
    /**
     * @var OwnerManagerModel
     */
    private $ownerManagerModel;

    /**
     * @var \Symfony\Component\HttpFoundation\Request|null
     */
    private $request;

    /**
     * @var LeadModel
     */
    private $leadModel;

    /**
     * @var CorePermissions
     */
    private $security;

    /**
     * @var NotificationModel
     */
    private $notificationModel;

    /**
     * @var UserModel
     */
    private $userModel;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * OwnerManagerSubscriber constructor.
     */
    public function __construct(
        OwnerManagerModel $ownerManagerModel,
        RequestStack $requestStack,
        LeadModel $leadModel,
        CorePermissions $security,
        NotificationModel $notificationModel,
        UserModel $userModel,
        TranslatorInterface $translator,
        RouterInterface $router
    ) {
        $this->ownerManagerModel = $ownerManagerModel;
        $this->request           = $requestStack->getCurrentRequest();
        $this->leadModel         = $leadModel;
        $this->security          = $security;
        $this->notificationModel = $notificationModel;
        $this->userModel         = $userModel;
        $this->translator        = $translator;
        $this->router            = $router;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            OwnerManagerEvents::OWNER_MANAGER_POST_SAVE => ['onOwnerManagerSave', 0],
        ];
    }

    public function onOwnerManagerSave(OwnerManagerEvent $ownerManagerEvent)
    {
        $ownerManager = $ownerManagerEvent->getOwnerManager();

        if (!$ownerManager->isPublished()) {
            return;
        }

        if (!$this->request) {
            return;
        }

        $allRequestsParamas = $this->request->request->all();
        if (empty($allRequestsParamas['owner_manager']['triggerExisting'])) {
            return;
        }

        switch ($ownerManager->getType()) {
            case LeadSubscriber::CONTACT_FIELD_CHANGE:
                $q = $this->contactIdsBasedOnContactField($ownerManager);
                break;
            case LeadSubscriber::POINT_CHANGE:
                $q = $this->contactIdsBasedOnPoints($ownerManager);
                break;
            case PageSubscriber::LANDING_PAGE_HIT:
                $q = $this->contactIdsBasedOnLandingPageHit($ownerManager);
                break;
            case PageSubscriber::TRACKING_PAGE_HIT:
                $q = $this->contactIdsBasedOnTrackingHit($ownerManager);
                break;
            default:
                return;
        }

        if (!$q instanceof QueryBuilder) {
            return;
        }

        $results = $q->execute()->fetchAll();
        if (empty($results)) {
            return;
        }

        $ids = array_column($results, 'id');

        if (empty($ids)) {
            return;
        }

        if (!is_array($ids)) {
            return;
        }

        $entities = $this->leadModel->getEntities(
            [
                'filter' => [
                    'force' => [
                        [
                            'column' => 'l.id',
                            'expr'   => 'in',
                            'value'  => $ids,
                        ],
                    ],
                ],
                'ignore_paginator' => true,
            ]
        );

        /** @var Lead $contact */
        foreach ($entities as $contact) {
            if ($this->security->hasEntityAccess(
                'lead:leads:editown',
                'lead:leads:editother',
                $contact->getPermissionUser()
            )) {
                $contact->setOwner($ownerManager->getOwner());
            }
        }

        $this->leadModel->saveEntities($entities);

        $this->notificationModel->addNotification(
            $this->translator->trans(
                'mautic.ownerManager.processed',
                [
                    '%count%'        => count($entities),
                    '%owner%'        => $ownerManager->getOwner()->getUsername(),
                    '%ownerManager%' => '<a href="'.$this->router->generate(
                            'mautic_ownermanager_action',
                            ['objectAction' => 'edit', 'objectId' => $ownerManager->getId()]
                        ).'" data-toggle="ajax">'.$ownerManager->getName().'</a>',
                ]
            ),
            'info',
            false,
            $this->translator->trans(
                'mautic.ownerManager.trigger.notification'
            ),
            'fa-users',
            null,
            $this->userModel->getSystemAdministrator()
        );
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    private function contactIdsBasedOnTrackingHit(OwnerManager $ownerManager)
    {
        $properties = $ownerManager->getProperties();
        $pageUrls   = ArrayHelper::getValue('page_url', $properties);
        $q          = $this->ownerManagerModel->getRepository()->getContactsToTriggerQuery($ownerManager);
        $q->innerJoin(
            'l',
            MAUTIC_TABLE_PREFIX.'page_hits',
            'ph',
            'ph.lead_id = l.id AND ph.page_id IS NULL  AND ph.email_id IS NULL  AND ph.redirect_id IS NULL'
        );

        $finds = explode('|', $pageUrls);
        $expr  = $q->expr()->orX();
        foreach ($finds as $find) {
            $parameter = 'value'.md5($find);
            $expr->add(
                $q->expr()->like('ph.url', sprintf(':%s', $parameter))
            );
            $q->setParameter($parameter, str_replace('*', '%', $find));
        }

        return $q->andWhere($expr);
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    private function contactIdsBasedOnLandingPageHit(OwnerManager $ownerManager)
    {
        $properties = $ownerManager->getProperties();
        $pages      = ArrayHelper::getValue('pages', $properties);

        $q = $this->ownerManagerModel->getRepository()->getContactsToTriggerQuery($ownerManager);
        $q->innerJoin('l', MAUTIC_TABLE_PREFIX.'page_hits', 'ph', 'ph.lead_id = l.id AND ph.page_id IS NOT NULL');
        if (!empty($pages)) {
            $q->andWhere($q->expr()->in('ph.page_id', $pages));
        }

        return $q->groupBy('l.id');
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder|void
     */
    private function contactIdsBasedOnPoints(OwnerManager $ownerManager)
    {
        $properties = $ownerManager->getProperties();
        $points     = ArrayHelper::getValue('points', $properties);

        if (is_null($points)) {
            return;
        }

        $q = $this->ownerManagerModel->getRepository()->getContactsToTriggerQuery($ownerManager);

        return $q->andWhere($q->expr()->gt('l.points', ':points'))->setParameter('points', $points);
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder|void
     */
    private function contactIdsBasedOnContactField(OwnerManager $ownerManager)
    {
        $properties = $ownerManager->getProperties();
        $field      = ArrayHelper::getValue('field', $properties);
        $value      = ArrayHelper::getValue('value', $properties);
        if (is_null($field) || is_null($value)) {
            return;
        }

        $q = $this->ownerManagerModel->getRepository()->getContactsToTriggerQuery($ownerManager);

        $finds = explode('|', $value);
        $expr  = $q->expr()->orX();
        foreach ($finds as $find) {
            $parameter = 'value'.md5($find);
            $expr->add(
                $q->expr()->like('l.'.$field, sprintf(':%s', $parameter))
            );
            $q->setParameter($parameter, str_replace('*', '%', $find));
        }

        return $q->andWhere($expr);
    }
}
