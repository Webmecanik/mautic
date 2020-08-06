<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticExtendEmailFieldsBundle\Controller\Api;

use FOS\RestBundle\Util\Codes;
use Mautic\ApiBundle\Controller\CommonApiController;
use MauticPlugin\MauticExtendEmailFieldsBundle\Entity\ExtendEmailFields;
use MauticPlugin\MauticExtendEmailFieldsBundle\Model\ExtendEmailFieldsModel;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class ExtendEmailFieldsApiController extends CommonApiController
{
    /**
     * @var \Mautic\CoreBundle\Model\AbstractCommonModel
     */
    private $emailModel;

    /**
     * @var ExtendEmailFieldsModel
     */
    private $extendEmailFieldModel;

    public function initialize(FilterControllerEvent $event)
    {
        /** @var ExtendEmailFieldsModel $extendEmailFieldModel */
        $this->extendEmailFieldModel            = $this->get('mautic.extendee.email.settings.model');
        $this->emailModel                       = $this->getModel('email.email');
    }

    /**
     * @param int $emailId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addAction($emailId)
    {
        $email = $this->emailModel->getEntity($emailId);
        if (!$email) {
            return $this->notFound();
        }
        if (empty($this->request->request->all())) {
            $view = $this->view(['error'=>'Parameters to update missing.'], Codes::HTTP_OK);

            return $this->handleView($view);
        }

        $this->extendEmailFieldModel->addOrEditEntity($email);

        $view  = $this->view(['success'=>'1'], Codes::HTTP_OK);

        return $this->handleView($view);
    }
}
