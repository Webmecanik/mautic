<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticOwnerManagerBundle\Controller\Api;

use FOS\RestBundle\Util\Codes;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\LeadBundle\Controller\LeadAccessTrait;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\MauticOwnerManagerBundle\Entity\OwnerManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class OwnerManagerApiController extends CommonApiController
{
    use LeadAccessTrait;

    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * {@inheritdoc}
     */
    public function initialize(FilterControllerEvent $event)
    {
        $this->model            = $this->getModel('ownerManager');
        $this->leadModel        = $this->getModel('lead');
        $this->entityClass      = OwnerManager::class;
        $this->entityNameOne    = 'ownermanager';
        $this->entityNameMulti  = 'ownermanager';
        $this->serializerGroups = ['categoryList', 'publishDetails'];

        parent::initialize($event);
    }
}
