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
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ActionUrlHitType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'page_url',
            TextType::class,
            [
                'label'      => 'mautic.page.point.action.form.page.url',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'       => 'form-control',
                    'tooltip'     => 'mautic.page.point.action.form.page.url.descr',
                    'placeholder' => 'https://',
                ],
            ]
        );

        $builder->add(
            'repeatable',
            YesNoButtonGroupType::class,
            [
                'label' => 'mautic.ownermanager.form.repeat',
                'data'  => isset($options['data']['repeatable']) ? $options['data']['repeatable'] : false,
            ]
        );
    }
}
