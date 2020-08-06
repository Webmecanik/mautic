<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\AutomationBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomAssetsEvent;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Loader\ParameterLoader;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

class AssetSubscriber implements EventSubscriberInterface
{
    /**
     * @var \Symfony\Component\HttpFoundation\Request|null
     */
    private $request;

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    public function __construct(RequestStack $requestStack, CoreParametersHelper $coreParametersHelper)
    {
        $this->request              = $requestStack->getCurrentRequest();
        $this->coreParametersHelper = $coreParametersHelper;
    }

    public static function getSubscribedEvents()
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_ASSETS => ['injectAssets', 0],
        ];
    }

    public function injectAssets(CustomAssetsEvent $assetsEvent)
    {
        $assetsEvent->addScriptDeclaration($this->addJs());
    }

    private function addJs()
    {
        $content = 'var email_creation_show_bcc  = '.(int) $this->coreParametersHelper->get('email_creation_show_bcc', false).';';

        return $content;
    }
}
