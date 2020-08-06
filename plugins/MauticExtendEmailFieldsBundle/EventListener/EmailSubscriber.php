<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticExtendEmailFieldsBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event as Events;
use MauticPlugin\MauticExtendEmailFieldsBundle\Helper\EmailSendEventModify;
use MauticPlugin\MauticExtendEmailFieldsBundle\Helper\ExtendeEmailFieldsIntegrationHelper;
use MauticPlugin\MauticExtendEmailFieldsBundle\Model\ExtendEmailFieldsModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EmailSubscriber implements EventSubscriberInterface
{
    protected $from;

    protected $contactFields;

    /**
     * @var ExtendEmailFieldsModel
     */
    private $extendEmailFieldsModel;

    /**
     * @var ExtendeEmailFieldsIntegrationHelper
     */
    private $emailFieldsIntegrationHelper;

    /**
     * EmailSubscriber constructor.
     */
    public function __construct(ExtendEmailFieldsModel $extendEmailFieldsModel, ExtendeEmailFieldsIntegrationHelper $emailFieldsIntegrationHelper)
    {
        $this->extendEmailFieldsModel       = $extendEmailFieldsModel;
        $this->emailFieldsIntegrationHelper = $emailFieldsIntegrationHelper;
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
     * Add an entry to the audit log.
     */
    public function onEmailPostSave(Events\EmailEvent $event)
    {
        if (!$this->emailFieldsIntegrationHelper->isActive()) {
            return;
        }

        $this->extendEmailFieldsModel->addOrEditEntity($event->getEmail());
    }

    /**
     * Add a delete entry to the audit log.
     */
    public function onEmailDelete(Events\EmailEvent $event)
    {
        if (!$this->emailFieldsIntegrationHelper->isActive()) {
            return;
        }

        $email          = $event->getEmail();
        $settingsExtend = $this->extendEmailFieldsModel->getRepository()->findOneBy(['email'=>$email]);
        if ($settingsExtend) {
            $this->extendEmailFieldsModel->getRepository()->deleteEntity($settingsExtend);
        }
    }
}
