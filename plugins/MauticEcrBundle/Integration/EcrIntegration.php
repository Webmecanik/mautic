<?php

/*
 * @copyright   2019 MTCExtendee. All rights reserved
 * @author      MTCExtendee
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEcrBundle\Integration;

use Mautic\CoreBundle\Form\DataTransformer\ArrayLinebreakTransformer;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilder;

class EcrIntegration extends AbstractIntegration
{
    const INTEGRATION_NAME = 'Ecr';

    const USER             = 'user';

    const KEY              = 'key';

    public function getName()
    {
        return self::INTEGRATION_NAME;
    }

    public function getDisplayName()
    {
        return 'ECR Sync';
    }

    public function getAuthenticationType()
    {
        return 'none';
    }

    /**
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return [
            self::USER => 'mautic.ecr.user',
            self::KEY  => 'mautic.ecr.key',
        ];
    }

    public function getIcon()
    {
        return 'plugins/MauticEcrBundle/Assets/img/icon.png';
    }

    /**
     * @param \Mautic\PluginBundle\Integration\Form|FormBuilder $builder
     * @param array                                             $data
     * @param string                                            $formArea
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ('features' == $formArea) {
            $builder->add('dnc', YesNoButtonGroupType::class, [
                'label' => 'mautic.ecr.sync.dnc',
                'data'  => (!isset($data['dnc'])) ? false : $data['dnc'],
            ]);

            $arrayLinebreakTransformer = new ArrayLinebreakTransformer();
            $builder->add(
                $builder->create(
                    'matchingFields',
                    TextareaType::class,
                    [
                        'label'      => 'mautic.ecr.matching.fields',
                        'label_attr' => ['class' => 'control-label'],
                        'attr'       => [
                            'class'   => 'form-control',
                            'tooltip' => 'mautic.ecr.matching.fields.tooltip',
                            'rows'    => 10,
                        ],
                    ]
                )->addViewTransformer($arrayLinebreakTransformer)
            );
        }
    }
}
