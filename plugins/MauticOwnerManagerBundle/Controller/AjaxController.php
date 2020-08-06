<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticOwnerManagerBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\InputHelper;
use MauticPlugin\MauticOwnerManagerBundle\Form\Type\OwnerManagerActionType;
use MauticPlugin\MauticOwnerManagerBundle\Form\Type\OwnerManagerType;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AjaxController.
 */
class AjaxController extends CommonAjaxController
{
    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *
     * @throws \Exception
     */
    protected function getActionFormAction(Request $request)
    {
        $dataArray = [
            'success' => 0,
            'html'    => '',
        ];
        $type      = InputHelper::clean($request->request->get('actionType'));

        if (!empty($type)) {
            //get the HTML for the form
            /** @var \MauticPlugin\MauticOwnerManagerBundle\Model\OwnerManagerModel $model */
            $model   = $this->getModel('ownermanager');
            $actions = $model->getOwnerManagerActions();
            if (isset($actions['actions'][$type])) {
                $themes = ['MauticOwnerManagerBundle:FormTheme\Action'];
                if (!empty($actions['actions'][$type]['formTheme'])) {
                    $themes[] = $actions['actions'][$type]['formTheme'];
                }

                $formType        = (!empty($actions['actions'][$type]['formType'])) ? $actions['actions'][$type]['formType'] : 'genericownermanager_settings';
                $formTypeOptions = (!empty($actions['actions'][$type]['formTypeOptions'])) ? $actions['actions'][$type]['formTypeOptions'] : [];
                $form            = $this->get('form.factory')->create(
                    OwnerManagerActionType::class,
                    [],
                    [
                        'formType'            => $formType,
                        'formTypeOptions'     => $formTypeOptions,
                    ]
                );
                $html            = $this->renderView(
                    'MauticOwnerManagerBundle:OwnerManager:actionform.html.php',
                    [
                        'form' => $this->setFormTheme(
                            $form,
                            'MauticOwnerManagerBundle:OwnerManager:actionform.html.php',
                            $themes
                        ),
                    ]
                );

                $html                 = str_replace('owner_manager_action', 'owner_manager', $html);
                $dataArray['html']    = $html;
                $dataArray['success'] = 1;
            }
        }

        return $this->sendJsonResponse($dataArray);
    }
}
