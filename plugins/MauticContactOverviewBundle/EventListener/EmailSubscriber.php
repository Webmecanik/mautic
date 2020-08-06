<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactOverviewBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\BuilderTokenHelper;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailBuilderEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\LeadBundle\LeadEvent;
use MauticPlugin\MauticContactOverviewBundle\Helper\TokenHelper;
use MauticPlugin\MauticContactOverviewBundle\Integration\ContactOverviewSettings;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class EmailSubscriber implements EventSubscriberInterface
{
    /**
     * @var ContactOverviewSettings
     */
    private $contactOverviewSettings;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * EmailSubscriber constructor.
     */
    public function __construct(ContactOverviewSettings $contactOverviewSettings, TranslatorInterface $translator, RouterInterface $router)
    {
        $this->contactOverviewSettings = $contactOverviewSettings;
        $this->translator              = $translator;
        $this->router                  = $router;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            EmailEvents::EMAIL_ON_BUILD   => ['onEmailBuild', 0],
            EmailEvents::EMAIL_ON_SEND    => ['onEmailGenerate', 0],
            EmailEvents::EMAIL_ON_DISPLAY => ['onEmailDisplay', 0],
        ];
    }

    /**
     * Add email to available page tokens.
     */
    public function onEmailBuild(EmailBuilderEvent $event)
    {
        if (!$this->contactOverviewSettings->isEnabled()) {
            return;
        }
        $event->addToken(
            ContactOverviewSettings::contactOverviewToken,
            $this->translator->trans('mautic.contactoverview.token')
        );
    }

    public function onEmailDisplay(EmailSendEvent $event)
    {
        $this->onEmailGenerate($event);
    }

    public function onEmailGenerate(EmailSendEvent $event)
    {
        if (!$this->contactOverviewSettings->isEnabled()) {
            return;
        }

        $leadId = !empty($event->getLead()['id']) ? $event->getLead()['id'] : 0;
        $hash   = base64_encode($this->contactOverviewSettings->encryption()->encrypt($leadId));
        $url    = $this->router->generate(
            'mautic_contactoverview_events',
            ['hash' => $hash],
            RouterInterface::ABSOLUTE_URL
        );

        $content = $event->getContent();
        $content = str_ireplace(
            'href="'.ContactOverviewSettings::contactOverviewToken,
            'mautic:disable-tracking href="'.ContactOverviewSettings::contactOverviewToken,
            $content
        );
        $event->addToken(ContactOverviewSettings::contactOverviewToken, $url);
        $event->setContent($content);
    }
}
