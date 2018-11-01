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

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class UpdateLeadActionType extends AbstractType
{
    const FIELD_TYPE_TO_REMOVE_VALUES = ['multiselect'];
    use EntityFieldsBuildFormTrait;

    private $factory;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var \Mautic\LeadBundle\Model\FieldModel $fieldModel */
        $fieldModel = $this->factory->getModel('lead.field');
        $leadFields = $fieldModel->getEntities(
            [
                'force' => [
                    [
                        'column' => 'f.isPublished',
                        'expr'   => 'eq',
                        'value'  => true,
                    ],
                ],
                'hydration_mode' => 'HYDRATE_ARRAY',
            ]
        );

        $builder->add(
            'fields_to_update',
            'leadfields_choices',
            [
                'label'       => '',
                'label_attr'  => ['class' => 'control-label'],
                'multiple'    => true,
                'object'      => 'lead',
                'empty_value' => 'mautic.core.select',
                'attr'        => [
                    'class'    => 'form-control',
                    'onchange' => 'Mautic.updateContactActionModifiers()',
                ],
            ]
        );

        $builder->add(
            'fields',
            UpdateFieldType::class,
            [
                'fields'  => $leadFields,
                'object'  => 'lead',
                'actions' => $options['data']['actions'],
                'data'    => $options['data']['fields'],
            ]
        );

        $builder->add(
            'actions',
            UpdateActionType::class,
            [
                'fields' => $leadFields,
                'data'   => $options['data']['actions'],
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'updatelead_action';
    }
}
