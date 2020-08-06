<?php

/*
 * @copyright   2019 MTCExtendee. All rights reserved
 * @author      MTCExtendee
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactOverviewBundle\Integration;

use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilder;

class ContactOverviewIntegration extends AbstractIntegration
{
    const INTEGRATION_NAME = 'ContactOverview';
    const DISPLAY_NAME     = 'Contact Overview';

    public function getName()
    {
        return self::INTEGRATION_NAME;
    }

    public function getDisplayName()
    {
        return self::DISPLAY_NAME;
    }

    public function getAuthenticationType()
    {
        return 'none';
    }

    public function getRequiredKeyFields()
    {
        return [
        ];
    }

    public function getIcon()
    {
        return 'plugins/MauticContactOverviewBundle/Assets/img/icon.png';
    }

    /**
     * @param \Mautic\PluginBundle\Integration\Form|FormBuilder $builder
     * @param array                                             $data
     * @param string                                            $formArea
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ('features' == $formArea || 'integration' == $formArea) {
            $builder->add(
                'events',
                ChoiceType::class,
                [
                    'choices'    => array_flip($this->leadModel->getEngagementTypes()),
                    'label'      => 'mautic.contactoverview.events',
                    'label_attr' => [
                        'class'       => 'control-label',
                        'data-toggle' => 'tooltip',
                        'title'       => $this->translator->trans('mautic.salesforce.form.activity.events.tooltip'),
                    ],
                    'multiple'   => true,
                    'required'   => false,
                ]
            );
        }
    }
}
