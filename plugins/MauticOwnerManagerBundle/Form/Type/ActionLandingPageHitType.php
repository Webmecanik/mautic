<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticOwnerManagerBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\PageBundle\Form\Type\PageListType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ActionLandingPageHitType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'pages',
            PageListType::class,
            [
                'label'      => 'mautic.page.point.action.form.page_urls',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.page.point.action.form.page_urls.tootlip',
                ],
            ]
        );

        $builder->add(
            'each',
            YesNoButtonGroupType::class,
            [
                'label' => 'mautic.ownermanager.form.each',
                'data'  => isset($options['data']['each']) ? $options['data']['each'] : false,
            ]
        );
    }
}
