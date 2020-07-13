<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form\Type;

use Mautic\FormBundle\Entity\Form;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GatedVideoType extends SlotType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'url',
            UrlType::class,
            [
                'label'      => 'Video URL',
                'label_attr' => ['class' => 'control-label'],
                'required'   => true,
                'attr'       => [
                    'class'           => 'form-control',
                    'data-slot-param' => 'gatedvideo-url',
                ],
            ]
        );

        $builder->add(
            'gatetime',
            TextType::class,
            [
                'label'      => 'Gate Time',
                'label_attr' => ['class' => 'control-label'],
                'required'   => true,
                'attr'       => [
                    'class'           => 'form-control',
                    'data-slot-param' => 'gatedvideo-gatetime',
                ],
            ]
        );

        $builder->add(
            'formid',
            EntityType::class,
            [
                'label'      => 'Form',
                'label_attr' => ['class' => 'control-label'],
                'required'   => true,
                'attr'       => [
                    'class'           => 'form-control',
                    'data-slot-param' => 'gatedvideo-formid',
                ],
                'placeholder'  => 'Select your form',
                'class'        => Form::class,
                'choice_label' => function ($form) {
                    return sprintf('%s (ID #%d)', $form->getName(), $form->getId());
                },
            ]
        );

        $builder->add(
            'width',
            TextType::class,
            [
                'label'      => 'Width',
                'label_attr' => ['class' => 'control-label'],
                'required'   => true,
                'attr'       => [
                    'class'           => 'form-control',
                    'data-slot-param' => 'gatedvideo-width',
                ],
            ]
        );

        $builder->add(
            'height',
            TextType::class,
            [
                'label'      => 'Height',
                'label_attr' => ['class' => 'control-label'],
                'required'   => true,
                'attr'       => [
                    'class'           => 'form-control',
                    'data-slot-param' => 'gatedvideo-height',
                ],
            ]
        );

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'width'  => 640,
                'height' => 320,
            ]
        );
    }
}
