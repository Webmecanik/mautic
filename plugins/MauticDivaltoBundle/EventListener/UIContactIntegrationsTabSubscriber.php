<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticDivaltoBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomTemplateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UIContactIntegrationsTabSubscriber implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_TEMPLATE => ['onTemplateRender', -5],
        ];
    }

    public function onTemplateRender(CustomTemplateEvent $event): void
    {
        if ('MauticLeadBundle:Lead:lead.html.php' === $event->getTemplate()) {
            $vars         = $event->getVars();
            $integrations = $vars['integrations'];

            foreach ($integrations as $key => $integration) {
                if ('Divalto' === $integration['integration']) {
                    $integrations[$key]['link'] = sprintf(
                        'https://weavy.divalto.com/page/%s/%s',
                        str_replace(['customercontact', 'marketinginbound'], ['customerContact', 'marketingInbound'], $integration['integration_entity']),
                        $integration['integration_entity_id']
                    );
                }
            }

            $vars['integrations'] = $integrations;

            $event->setVars($vars);
        }
    }
}
