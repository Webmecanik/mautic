<?php

declare(strict_types=1);

namespace MauticPlugin\BrickBuilderBundle\EventSubscriber;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomContentEvent;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\TemplatingHelper;
use Mautic\EmailBundle\Entity\Email;
use MauticPlugin\BrickBuilderBundle\AutoSave\AutoSaveEmail;
use MauticPlugin\BrickBuilderBundle\Entity\BrickBuilder;
use MauticPlugin\BrickBuilderBundle\Helper\FileManager;
use MauticPlugin\BrickBuilderBundle\Model\BrickBuilderModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

class InjectCustomContentSubscriber implements EventSubscriberInterface
{
    /**
     * @var BrickBuilderModel
     */
    private $brickBuilderModel;

    /**
     * @var TemplatingHelper
     */
    private $templatingHelper;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var AutoSaveEmail
     */
    private $autoSave;

    /**
     * @var \Symfony\Component\HttpFoundation\Request|null
     */
    private $request;

    /**
     * @var string
     */
    private $customAutoSave;

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * InjectCustomContentSubscriber constructor.
     */
    public function __construct(
        BrickBuilderModel $brickBuilderModel,
        TemplatingHelper $templatingHelper,
        RequestStack $requestStack,
        RouterInterface $router,
        AutoSaveEmail $autoSave,
        CoreParametersHelper $coreParametersHelper
    ) {
        $this->brickBuilderModel    = $brickBuilderModel;
        $this->templatingHelper     = $templatingHelper;
        $this->requestStack         = $requestStack;
        $this->request              = $requestStack->getCurrentRequest();
        $this->router               = $router;
        $this->autoSave             = $autoSave;
        $this->coreParametersHelper = $coreParametersHelper;
    }

    public static function getSubscribedEvents()
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_CONTENT => ['injectViewCustomContent', 0],
        ];
    }

    public function injectViewCustomContent(CustomContentEvent $customContentEvent)
    {
        if (!$this->coreParametersHelper->get(BrickBuilder::BRICK_BUILDER_ENABLE)) {
            return;
        }

        $parameters = $customContentEvent->getVars();

        if ('email.settings.advanced' === $customContentEvent->getContext()) {
            // Inject MJML form within mail page
            if (empty($parameters['email']) || !$parameters['email'] instanceof Email) {
                return;
            }
            $passParams = ['customMjml' => ''];
            if ($this->requestStack->getCurrentRequest()->request->has('brickbuilder')) {
                $data = $this->requestStack->getCurrentRequest()->get('brickbuilder', '');

                if (isset($data['customMjml'])) {
                    $passParams['customMjml'] = $data['customMjml'];
                }
            }

            $brickBuilder = $this->brickBuilderModel->getRepository()->findOneBy(['email' => $parameters['email']]);
            if ($brickBuilder instanceof BrickBuilder && 'POST' !== $this->requestStack->getCurrentRequest()->getMethod(
                ) && !$this->requestStack->getCurrentRequest()->request->has('brickbuilder')) {
                $passParams['customMjml'] = $brickBuilder->getCustomMjml();
            }

            $passParams['customAutoSave'] = '';
            if ($this->autoSave->has($parameters['email'])) {
                $this->customAutoSave =  $passParams['customAutoSave'] = $this->autoSave->get($parameters['email']);
            }

            $content = $this->templatingHelper->getTemplating()->render(
                'BrickBuilderBundle:Setting:fields.html.php',
                $passParams
            );

            $customContentEvent->addContent($content);
        } elseif ('page.header.right' === $customContentEvent->getContext()) {
            if ($this->customAutoSave && 'edit' == $this->request->get('objectAction') && 'mautic_email_action' == $this->request->get('_route')) {
                $content = $this->templatingHelper->getTemplating()->render(
                    'BrickBuilderBundle:Builder:autosave.html.php'
                );

                $customContentEvent->addContent($content);
            }
        }
    }
}
