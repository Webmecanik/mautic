<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactOverviewBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\TemplatingHelper;
use Mautic\LeadBundle\Controller\LeadDetailsTrait;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\MauticContactOverviewBundle\Integration\ContactOverviewSettings;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class ContactController extends CommonController
{
    use LeadDetailsTrait;

    /**
     * @var LeadModel
     */
    private $leadModel;

    /**
     * @var TemplatingHelper
     */
    private $templatingHelper;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var ContactOverviewSettings
     */
    private $contactOverviewSettings;

    /**
     * @return mixed|void
     */
    public function initialize(FilterControllerEvent $event)
    {
        $this->leadModel               = $this->container->get('mautic.lead.model.lead');
        $this->templatingHelper        = $this->container->get('mautic.helper.templating');
        $this->session                 = $this->container->get('session');
        $this->contactOverviewSettings = $this->container->get('mautic.contactoverview.integration.settings');
    }

    /**
     * @param int $page
     *
     * @return JsonResponse
     */
    public function overviewAction($page = 0)
    {
        $hash   = $this->request->get('hash');
        $leadId = $this->contactOverviewSettings->encryption()->decrypt(base64_decode($hash));
        $lead   = $this->leadModel->getEntity($leadId);

        if (!$lead) {
            return $this->notFound();
        }
        if ('list' == $this->request->get('tmpl')) {
            $this->setListFilters();

            $filters = null;

            $order = [
                $this->session->get('mautic.lead.'.$leadId.'.timeline.orderby'),
                $this->session->get('mautic.lead.'.$leadId.'.timeline.orderbydir'),
            ];

            return $this->delegateView(
                [
                    'viewParameters'  => [
                        'lead'   => $lead,
                        'page'   => $page,
                        'events' => $this->getEngagements($lead, $filters, $order, $page),
                        'hash'   => $hash,
                    ],
                    'contentTemplate' => 'MauticContactOverviewBundle:Contact:list.html.php',
                ]
            );
        }

        $content = $this->templatingHelper->getTemplating()->renderResponse(
            'MauticContactOverviewBundle:Contact:overview.html.php',
            [
                'lead'   => $lead,
                'events' => $this->getEngagements($lead),
                'hash'   => $hash,
            ]
        )->getContent();

        return new Response($content);
    }

    /**
     * @param int $page
     * @param int $limit
     *
     * @return array
     */
    protected function getEngagements(Lead $lead, array $filters = null, array $orderBy = null, $page = 1, $limit = 25)
    {
        $session = $this->get('session');
        if (null == $filters) {
            $filters = $session->get(
                'mautic.lead.'.$lead->getId().'.timeline.filters',
                [
                    'search'        => '',
                    'includeEvents' => $this->contactOverviewSettings->getEvents(),
                    'excludeEvents' => [],
                ]
            );
        }

        if (null == $orderBy) {
            if (!$session->has('mautic.lead.'.$lead->getId().'.timeline.orderby')) {
                $session->set('mautic.lead.'.$lead->getId().'.timeline.orderby', 'timestamp');
                $session->set('mautic.lead.'.$lead->getId().'.timeline.orderbydir', 'DESC');
            }

            $orderBy = [
                $session->get('mautic.lead.'.$lead->getId().'.timeline.orderby'),
                $session->get('mautic.lead.'.$lead->getId().'.timeline.orderbydir'),
            ];
        }
        /** @var LeadModel $model */
        $model = $this->getModel('lead');

        return $model->getEngagements($lead, $filters, $orderBy, $page, $limit);
    }
}
