<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticYellowboxCrmBundle\EventListener;

use Mautic\IntegrationsBundle\Event\FormLoadEvent;
use Mautic\IntegrationsBundle\IntegrationEvents;
use MauticPlugin\MauticYellowboxCrmBundle\Integration\YellowboxCrmIntegration;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository\Cache\FieldCache;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConfigFormLoadSubscriber implements EventSubscriberInterface
{
    /**
     * @var FieldCache
     */
    private $fieldCache;

    public function __construct(FieldCache $fieldCache)
    {
        $this->fieldCache = $fieldCache;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            IntegrationEvents::INTEGRATION_CONFIG_FORM_LOAD => ['onConfigFormLoad', 0],
        ];
    }

    public function onConfigFormLoad(FormLoadEvent $event): void
    {
        if (YellowboxCrmIntegration::NAME !== $event->getIntegration()) {
            return;
        }

        $this->fieldCache->ClearCacheForConfigForm();
    }
}
