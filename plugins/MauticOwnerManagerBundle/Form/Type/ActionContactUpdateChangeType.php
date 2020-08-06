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
use Mautic\LeadBundle\Form\Type\LeadFieldsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ActionContactUpdateChangeType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'field',
            LeadFieldsType::class,
            [
                'label'       => 'mautic.page.point.action.form.contact.field',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => [
                    'class'   => 'form-control',
                ],
                'required'    => false,
                'placeholder' => '',
                'multiple'    => false,
            ]
        );

        $builder->add(
            'value',
            TextType::class,
            [
                'label'      => 'mautic.page.point.action.form.contact.field.value',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'tooltip'       => 'mautic.page.point.action.form.contact.field.value.tooltip',
                    'class'         => 'form-control',
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
