<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticExtendEmailFieldsBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomContentEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\TemplatingHelper;
use Mautic\EmailBundle\Entity\Email;
use MauticPlugin\MauticExtendEmailFieldsBundle\Entity\ExtendEmailFields;
use MauticPlugin\MauticExtendEmailFieldsBundle\Helper\ExtendeEmailFieldsIntegrationHelper;
use MauticPlugin\MauticExtendEmailFieldsBundle\Model\ExtendEmailFieldsModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class InjectCustomContentSubscriber implements EventSubscriberInterface
{
    /**
     * @var TemplatingHelper
     */
    private $templatingHelper;

    /**
     * @var ExtendEmailFieldsModel
     */
    private $extendEmailFieldsModel;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var ExtendeEmailFieldsIntegrationHelper
     */
    private $emailFieldsIntegrationHelper;

    /**
     * InjectCustomContentSubscriber constructor.
     */
    public function __construct(TemplatingHelper $templatingHelper, ExtendEmailFieldsModel $extendEmailFieldsModel, RequestStack $requestStack, ExtendeEmailFieldsIntegrationHelper $emailFieldsIntegrationHelper)
    {
        $this->templatingHelper             = $templatingHelper;
        $this->extendEmailFieldsModel       = $extendEmailFieldsModel;
        $this->requestStack                 = $requestStack;
        $this->emailFieldsIntegrationHelper = $emailFieldsIntegrationHelper;
    }

    public static function getSubscribedEvents()
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_CONTENT => ['injectViewCustomContent', 0],
        ];
    }

    public function injectViewCustomContent(CustomContentEvent $customContentEvent)
    {
        if (!$this->emailFieldsIntegrationHelper->isActive()) {
            return;
        }

        $parameters = $customContentEvent->getVars();
        if ('email.settings.advanced' != $customContentEvent->getContext()) {
            return;
        } elseif (empty($parameters['email']) || !$parameters['email'] instanceof Email) {
            return;
        }

        $passParams           = ['form'=>$parameters['form']];
        $passParams['extra1'] = $this->requestStack->getCurrentRequest()->get('extra1');
        $passParams['extra2'] = $this->requestStack->getCurrentRequest()->get('extra2');
        /** @var ExtendEmailFields $emailFieldss */
        $emailFieldss = $this->extendEmailFieldsModel->getRepository()->findOneBy(['email'=> $parameters['email']]);
        if ($emailFieldss instanceof ExtendEmailFields && 'POST' !== $this->requestStack->getCurrentRequest()->getMethod()) {
            $passParams['extra1'] = $emailFieldss->getExtra1();
            $passParams['extra2'] = $emailFieldss->getExtra2();
        }

        $passParams['labelExtra1'] = $this->emailFieldsIntegrationHelper->getLabel('extra1');
        $passParams['labelExtra2'] = $this->emailFieldsIntegrationHelper->getLabel('extra2');

        $content = $this->templatingHelper->getTemplating()->render(
            'MauticExtendEmailFieldsBundle:Email:settings.html.php',
            $passParams
        );
        $customContentEvent->addContent($content);
    }
}
