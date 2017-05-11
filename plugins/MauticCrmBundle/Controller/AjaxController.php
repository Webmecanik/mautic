<?php
/**
 * @copyright   2016 Webmecanik
 * @author      Webmecanik
 *
 * @link        http://www.webmecanik.com
 */

namespace MauticPlugin\MauticCrmBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AjaxController.
 */
class AjaxController extends CommonAjaxController
{
    /**
     * INES : Check connection to INES web service, used by the test button of the plugin's config tab.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request Do not fill this argument
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse string
     */
    protected function inesCheckConnexionAction(Request $request)
    {
        $inesIntegration = $this->container->get('mautic.helper.integration')->getIntegrationObject('Ines');

        $isConnexionOk = $inesIntegration->checkAuth();

        $message = $this->translator->trans(
            $isConnexionOk ? 'mautic.ines.form.check.success' : 'mautic.ines.form.check.fail'
        );

        return $this->sendJsonResponse([
            'message' => $message,
        ]);
    }
}