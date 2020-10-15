<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\BrickBuilderBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Controller\AjaxLookupControllerTrait;
use Mautic\CoreBundle\Controller\VariantAjaxControllerTrait;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\EmailBundle\Helper\PlainTextHelper;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\PageBundle\Form\Type\AbTestPropertiesType;
use MauticPlugin\BrickBuilderBundle\AutoSave\AutoSaveAbstract;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AjaxController extends CommonAjaxController
{
    use AjaxLookupControllerTrait;

    /**
     * @param $objectType
     * @param $objectId
     */
    public function autosaveAction(Request $request): JsonResponse
    {
        $objectType =  $request->get('objectType');
        if (!in_array($objectType, BrickController::OBJECT_TYPE)) {
            return $this->notFound();
        }

        $objectId =  $request->get('objectId');
        if (!$email = $this->getModel('email')->getEntity($objectId)) {
            return $this->notFound();
        }

        if (!$user = $this->get('mautic.helper.user')->getUser()) {
            return $this->notFound();
        }

        /** @var AutoSaveAbstract $autoSave */
        $autoSave = $this->get('brickbuilder.service.autosave.email');
        $autoSave->set($request->request->all()['state'], $email);

        $response = ['success' => 1];

        return $this->sendJsonResponse($response);
    }
}
