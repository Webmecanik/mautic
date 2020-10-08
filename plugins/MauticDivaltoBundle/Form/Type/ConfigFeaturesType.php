<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDivaltoBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class ConfigFeaturesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'settings',
            ChoiceType::class,
            [
                'choices' => [
                    'divalto.sync.contacts.company' => 'syncContactsCompany',
                    'divalto.debug.mode.enable'     => 'debugMode',
                ],
                'expanded'    => true,
                'multiple'    => true,
                'label'       => 'mautic.core.settings',
                'label_attr'  => ['class' => 'control-label'],
                'placeholder' => false,
                'required'    => false,
            ]
        );
    }
}
