<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Controller;

use Mautic\ApiBundle\Helper\RequestHelper;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;

/**
 * Class ExceptionController.
 */
class ExceptionController extends CommonController
{
    /**
     * {@inheritdoc}
     */
    public function showAction(Request $request, FlattenException $exception, DebugLoggerInterface $logger = null)
    {
        $class          = $exception->getClass();
        $currentContent = $this->getAndCleanOutputBuffering($request->headers->get('X-Php-Ob-Level', -1));
        $layout         = 'prod' == MAUTIC_ENV ? 'Error' : 'Exception';
        $code           = $exception->getStatusCode();

        if (0 === $code) {
            //thrown exception that didn't set a code
            $code = 500;
        }

        // Special handling for oauth and api urls
        if (
            (false !== strpos($request->getUri(), '/oauth') && false === strpos($request->getUri(), 'authorize'))
            || RequestHelper::isApiRequest($request)
            || (!defined('MAUTIC_AJAX_VIEW') && false !== strpos($request->server->get('HTTP_ACCEPT', ''), 'application/json'))
        ) {
            $allowRealMessage =
                'dev' === MAUTIC_ENV ||
                false !== strpos($class, 'UnexpectedValueException') ||
                false !== strpos($class, 'NotFoundHttpException') ||
                false !== strpos($class, 'AccessDeniedHttpException');

            $message   = $allowRealMessage
                ? $exception->getMessage()
                : $this->get('translator')->trans(
                    'mautic.core.error.generic',
                    ['%code%' => $code]
                );
            $dataArray = [
                'errors' => [
                    [
                        'message' => $message,
                        'code'    => $code,
                        'type'    => null,
                    ],
                ],
            ];

            if ('dev' == MAUTIC_ENV) {
                $dataArray['trace'] = $exception->getTrace();
            }

            // Normal behavior in Symfony dev mode is to send 200 with error message,
            // but this is used in prod mode for all "/api" requests too. (#224)
            return new JsonResponse($dataArray, $code);
        }

        if ($request->get('prod')) {
            $layout = 'Error';
        }

        $anonymous    = $this->get('mautic.security')->isAnonymous();
        $baseTemplate = 'MauticCoreBundle:Default:slim.html.php';
        if ($anonymous) {
            if ($templatePage = $this->get('mautic.helper.theme')->getTheme()->getErrorPageTemplate($code)) {
                $baseTemplate = $templatePage;
            }
        }

        $template   = "MauticCoreBundle:{$layout}:{$code}.html.php";
        $templating = $this->get('mautic.helper.templating')->getTemplating();
        if (!$templating->exists($template)) {
            $template = "MauticCoreBundle:{$layout}:base.html.php";
        }

        $statusText = isset(Response::$statusTexts[$code]) ? Response::$statusTexts[$code] : '';

        $url      = $request->getRequestUri();
        $urlParts = parse_url($url);

        /***/
        $currentUser = $this->factory->getUser();
        $completUrl  = $_SERVER['HTTP_HOST'].$url;

        // construction du mail pour "report an issue"
        $subject = $this->buildSubjectMail($code);
        $body    = $this->buildBodyMailFromException($currentUser, $completUrl, $exception);

        $mailDest    = $this->coreParametersHelper->getParameter('mail_error_support_manual_report', 'support@webmecanik.com');
        $mailSupport = $mailDest.'?subject='.$subject.'&body='.$body;
        /***/

        return $this->delegateView(
            [
                'viewParameters'  => [
                    'baseTemplate'   => $baseTemplate,
                    'status_code'    => $code,
                    'status_text'    => $statusText,
                    'exception'      => $exception,
                    'logger'         => $logger,
                    'currentContent' => $currentContent,
                    'isPublicPage'   => $anonymous,
                    'mailSupport'    => $mailSupport,
                ],
                'contentTemplate' => $template,
                'passthroughVars' => [
                    'error' => [
                        'code'      => $code,
                        'text'      => $statusText,
                        'exception' => ('dev' == MAUTIC_ENV) ? $exception->getMessage() : '',
                        'trace'     => ('dev' == MAUTIC_ENV) ? $exception->getTrace() : '',
                    ],
                    'route' => $urlParts['path'],
                ],
                'responseCode'    => $code,
            ]
        );
    }

    /**
     * @param int $startObLevel
     *
     * @return string
     */
    protected function getAndCleanOutputBuffering($startObLevel)
    {
        if (ob_get_level() <= $startObLevel) {
            return '';
        }

        Response::closeOutputBuffers($startObLevel + 1, true);

        return ob_get_clean();
    }

    protected function extractCode($exception)
    {
        $code = $exception->getStatusCode();
        if (0 === $code) {
            //thrown exception that didn't set a code
            $code = 500;
        }

        return $code;
    }

    /**
     * construct subject mail.
     */
    protected function buildSubjectMail($code)
    {
        $subject = sprintf(
            '%s: %s',
            $this->coreParametersHelper->get('custom_exception_email_subject'),
            $code
        );

        return $subject;
    }

    /**
     * @param User   $user
     * @param string $url
     * @param string $exception
     *
     * @return string
     */
    protected function buildBodyMailFromException($user, $url, $exception)
    {
        $code         = $this->extractCode($exception);
        $errorMessage = $exception->getMessage();

        return $this->renderView(
            'MauticCoreBundle:Error:email-body.html.php',
            [
                'message'      => $errorMessage,
                'code'         => $code,
                'url'          => $url,
                'user'         => $user,
            ]
        );
    }
}
