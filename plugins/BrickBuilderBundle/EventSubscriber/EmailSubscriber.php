<?php

declare(strict_types=1);

namespace MauticPlugin\BrickBuilderBundle\EventSubscriber;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event as Events;
use MauticPlugin\BrickBuilderBundle\AutoSave\AutoSaveEmail;
use MauticPlugin\BrickBuilderBundle\Entity\BrickBuilder;
use MauticPlugin\BrickBuilderBundle\Model\BrickBuilderModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class EmailSubscriber implements EventSubscriberInterface
{
    /**
     * @var BrickBuilderModel
     */
    private $brickBuilderModel;

    /**
     * @var AutoSaveEmail
     */
    private $autoSaveEmail;

    /**
     * @var \Symfony\Component\HttpFoundation\Request|null
     */
    private $request;

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * EmailSubscriber constructor.
     */
    public function __construct(BrickBuilderModel $brickBuilderModel, AutoSaveEmail $autoSaveEmail, RequestStack $requestStack, CoreParametersHelper $coreParametersHelper)
    {
        $this->brickBuilderModel    = $brickBuilderModel;
        $this->autoSaveEmail        = $autoSaveEmail;
        $this->request              = $requestStack->getCurrentRequest();
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            EmailEvents::EMAIL_POST_SAVE   => ['onEmailPostSave', 0],
            EmailEvents::EMAIL_POST_DELETE => ['onEmailDelete', 0],
        ];
    }

    /**
     * Add an entry.
     */
    public function onEmailPostSave(Events\EmailEvent $event)
    {
        if (!$this->coreParametersHelper->get(BrickBuilder::BRICK_BUILDER_ENABLE)) {
            return;
        }

        $this->brickBuilderModel->addOrEditEntity($event->getEmail());

        if ('POST' === $this->request->getMethod() && $this->request->request->get('emailform')) {
            $this->autoSaveEmail->delete($event->getEmail());
        }
    }

    /**
     * Delete an entry.
     */
    public function onEmailDelete(Events\EmailEvent $event)
    {
        if (!$this->coreParametersHelper->get(BrickBuilder::BRICK_BUILDER_ENABLE)) {
            return;
        }

        $email           = $event->getEmail();
        $brickBuilder    = $this->brickBuilderModel->getRepository()->findOneBy(['email' => $email]);

        if ($brickBuilder) {
            $this->brickBuilderModel->getRepository()->deleteEntity($brickBuilder);
        }
    }
}
