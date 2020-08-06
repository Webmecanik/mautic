<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticOwnerManagerBundle\EventListener;

use Mautic\CoreBundle\Helper\ArrayHelper;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Event\PageHitEvent;
use Mautic\PageBundle\PageEvents;
use MauticPlugin\MauticOwnerManagerBundle\Event\OwnerManagerBuilderEvent;
use MauticPlugin\MauticOwnerManagerBundle\Event\OwnerManagerChangeActionExecutedEvent;
use MauticPlugin\MauticOwnerManagerBundle\Form\Type\ActionLandingPageHitType;
use MauticPlugin\MauticOwnerManagerBundle\Form\Type\ActionUrlHitType;
use MauticPlugin\MauticOwnerManagerBundle\Helper\MatchHelper;
use MauticPlugin\MauticOwnerManagerBundle\Model\OwnerManagerModel;
use MauticPlugin\MauticOwnerManagerBundle\OwnerManagerEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PageSubscriber implements EventSubscriberInterface
{
    const LANDING_PAGE_HIT  = 'landing_page.hit';
    const TRACKING_PAGE_HIT = 'tracking_page.hit';

    /**
     * @var OwnerManagerModel
     */
    private $ownerManagerModel;

    /**
     * PageHitSubscriber constructor.
     */
    public function __construct(OwnerManagerModel $ownerManagerModel)
    {
        $this->ownerManagerModel = $ownerManagerModel;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            OwnerManagerEvents::OWNERMANAGER_ON_BUILD          => ['onOwnerManagerBuild', 0],
            OwnerManagerEvents::OWNERMANAGER_ON_ACTION_EXECUTE => ['onActionExecute', 0],
            PageEvents::PAGE_ON_HIT                            => ['onPageHit', 0],
        ];
    }

    public function onOwnerManagerBuild(OwnerManagerBuilderEvent $event)
    {
        $action = [
            'group'       => 'mautic.ownermanager.page.action',
            'label'       => 'mautic.ownermanager.action.urlhit',
            'description' => 'mautic.ownermanager.action.urlhit_descr',
            'eventName'   => OwnerManagerEvents::OWNERMANAGER_ON_ACTION_EXECUTE,
            'formType'    => ActionUrlHitType::class,
        ];

        $event->addAction(self::TRACKING_PAGE_HIT, $action);

        $action = [
            'group'       => 'mautic.ownermanager.page.action',
            'label'       => 'mautic.ownermanager.action.pagehit',
            'description' => 'mautic.ownermanager.action.pagehit_descr',
            'eventName'   => OwnerManagerEvents::OWNERMANAGER_ON_ACTION_EXECUTE,
            'formType'    => ActionLandingPageHitType::class,
        ];

        $event->addAction(self::LANDING_PAGE_HIT, $action);
    }

    /**
     * @throws \Exception
     */
    public function onPageHit(PageHitEvent $event)
    {
        if ($event->getPage()) {
            $this->ownerManagerModel->triggerAction(self::LANDING_PAGE_HIT, $event->getHit(), $event->getHit()->getLead(), $event->getPage()->getId());
        } else {
            $this->ownerManagerModel->triggerAction(self::TRACKING_PAGE_HIT, $event->getHit(), $event->getHit()->getLead(), md5($event->getHit()->getUrl()));
        }
    }

    /**
     * @return bool|void
     */
    public function onActionExecute(OwnerManagerChangeActionExecutedEvent $event)
    {
        if ($event->checkContext(self::TRACKING_PAGE_HIT)) {
            $this->urlHitAction($event);
        } elseif ($event->checkContext(self::LANDING_PAGE_HIT)) {
            $this->landingPageHitAction($event);
        }
    }

    private function landingPageHitAction(OwnerManagerChangeActionExecutedEvent $event)
    {
        $hit = $event->getEventDetails();
        if (!$hit instanceof Hit) {
            return;
        }

        if (!$hit->getPage() instanceof Page) {
            return;
        }

        $pageHit = $hit->getPage();

        list($parent, $children) = $pageHit->getVariants();
        //use the parent (self or configured parent)
        $pageHitId = $parent->getId();

        // If no pages are selected, the pages array does not exist
        $limitToPages = ArrayHelper::getValue('pages', $event->getOwnerManager()->getProperties());

        if (!empty($limitToPages) && !in_array($pageHitId, $limitToPages)) {
            //no points change
            return;
        }

        $each = ArrayHelper::getValue('each', $event->getOwnerManager()->getProperties());

        if ($each) {
            $event->setSucceedIfNotAlreadyTriggeredByInternalId($pageHitId);
        } else {
            $event->setSucceedIfNotAlreadyTriggered();
        }

        $event->setSucceedIfNotAlreadyTriggered();
    }

    /**
     * @return bool|void
     */
    private function urlHitAction(OwnerManagerChangeActionExecutedEvent $event)
    {
        $hit = $event->getEventDetails();
        if (!$hit instanceof Hit) {
            return;
        }
        $ownerManager = $event->getOwnerManager();

        $url        = $hit->getUrl();
        $limitToUrl = html_entity_decode(trim(ArrayHelper::getValue('page_url', $ownerManager->getProperties())));

        if (!MatchHelper::matchRegexp($limitToUrl, $url)) {
            return;
        }

        $repeatable = ArrayHelper::getValue('repeatable', $event->getOwnerManager()->getProperties());

        if ($repeatable) {
            $event->setSucceded();
        } else {
            $event->setSucceedIfNotAlreadyTriggered();
        }
    }
}
