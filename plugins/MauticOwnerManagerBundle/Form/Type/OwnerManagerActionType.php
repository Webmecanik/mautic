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

use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class PointActionType.
 */
class OwnerManagerActionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $masks           = [];
        $formTypeOptions = [
            'label' => false,
        ];
        if (!empty($options['formTypeOptions'])) {
            $formTypeOptions = array_merge($formTypeOptions, $options['formTypeOptions']);
        }
        $builder->add('properties', $options['formType'], $formTypeOptions);

        if (isset($options['settings']['formTypeCleanMasks'])) {
            $masks['properties'] = $options['settings']['formTypeCleanMasks'];
        }

        $builder->addEventSubscriber(new CleanFormSubscriber($masks));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'formType'        => GenericOwnerManagerSettingsType::class,
            'formTypeOptions' => [],
        ]);
    }
}
