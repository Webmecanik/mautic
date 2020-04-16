<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UpdateActionType extends AbstractType
{
    const FIELD_TYPE_TO_UPDATE_VALUES = ['multiselect'];

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $object = isset($options['object']) ? $options['object'] : 'lead';
        foreach ($options['fields'] as $field) {
            if ($field['isPublished'] === false || $field['object'] !== $object) {
                continue;
            }

            $alias                                       = $field['alias'];
            $choices                                     = ['mautic.campaign.lead.field.update' => 'update'];
            $choices['mautic.campaign.lead.field.empty'] = 'empty';

            if (in_array($field['type'], self::FIELD_TYPE_TO_UPDATE_VALUES)) {
                $choices['mautic.campaign.lead.field.add.values']    = 'add';
                $choices['mautic.campaign.lead.field.remove.values'] = 'remove';
            }

            $builder->add(
                $alias,
                'choice',
                [
                    'label'      => '',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'    => 'form-control',
                        'onchange' => 'Mautic.updateContactActionModifier(this)',
                    ],
                    'choices'           => $choices,
                    'choices_as_values' => true,
                ]
            );
        }
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(['fields', 'object']);
    }
}
