<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDivaltoBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigAuthType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $password       = null;
        $configProvider = $options['integration'];
        if ($configProvider->getIntegrationConfiguration() && $configProvider->getIntegrationConfiguration()->getApiKeys()) {
            $data     = $configProvider->getIntegrationConfiguration()->getApiKeys();
            $password = $data['password'] ?? null;
        }

        $builder->add(
            'projectCode',
            TextType::class,
            [
                'label'      => 'divalto.projectCode',
                'label_attr' => ['class' => 'control-label'],
                'required'   => true,
                'attr'       => [
                    'class' => 'form-control',
                ],
            ]
        );

        $builder->add(
            'username',
            TextType::class,
            [
                'label'      => 'divalto.username',
                'label_attr' => ['class' => 'control-label'],
                'required'   => true,
                'attr'       => [
                    'class' => 'form-control',
                ],
            ]
        );

        $builder->add(
            'password',
            TextType::class,
            [
                'label'      => 'divalto.password',
                'label_attr' => ['class' => 'control-label'],
                'required'   => true,
                'attr'       => [
                    'class' => 'form-control',
                ],
                'data'=> $password,
            ]
        );
    }

    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefaults(
            [
                'integration' => null,
            ]
        );
    }
}
