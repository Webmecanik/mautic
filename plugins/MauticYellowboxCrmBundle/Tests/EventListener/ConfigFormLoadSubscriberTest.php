<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticYellowboxCrmBundle\Tests\EventListener;

use Mautic\IntegrationsBundle\Event\FormLoadEvent;
use Mautic\IntegrationsBundle\IntegrationEvents;
use MauticPlugin\MauticYellowboxCrmBundle\EventListener\ConfigFormLoadSubscriber;
use MauticPlugin\MauticYellowboxCrmBundle\Integration\YellowboxCrmIntegration;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository\Cache\FieldCache;

class ConfigFormLoadSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ConfigFormLoadSubscriber
     */
    private $subscriber;

    public function setUp()
    {
        $fieldCache       = $this->createMock(FieldCache::class);
        $this->subscriber = new ConfigFormLoadSubscriber($fieldCache);
    }

    public function testOnConfigFormLoad()
    {
        $event = $this->createMock(FormLoadEvent::class);
        $event->expects($this->once())
            ->method('getIntegration')
            ->willReturn('nonsense');
        $this->assertNull($this->subscriber->onConfigFormLoad($event));

        $fieldCache = $this->createMock(FieldCache::class);
        $fieldCache->expects($this->once())
            ->method('ClearCacheForConfigForm');
        $subscriber = new ConfigFormLoadSubscriber($fieldCache);
        $event      = $this->createMock(FormLoadEvent::class);
        $event->expects($this->once())
            ->method('getIntegration')
            ->willReturn(YellowboxCrmIntegration::NAME);
        $this->assertNull($subscriber->onConfigFormLoad($event));
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [IntegrationEvents::INTEGRATION_CONFIG_FORM_LOAD => ['onConfigFormLoad', 0]],
            $this->subscriber->getSubscribedEvents()
        );
    }
}