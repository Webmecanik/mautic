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

use Doctrine\ORM\EntityManager;
use Mautic\CategoryBundle\Form\Type\CategoryListType;
use Mautic\CoreBundle\Form\DataTransformer\IdToEntityModelTransformer;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\UserBundle\Form\Type\UserListType;
use MauticPlugin\MauticOwnerManagerBundle\Entity\OwnerManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class OwnerManagerType extends AbstractType
{
    /**
     * @var \Mautic\CoreBundle\Security\Permissions\CorePermissions
     */
    private $security;

    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(CorePermissions $security, EntityManager $entityManager)
    {
        $this->security      = $security;
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(['description' => 'html']));
        $builder->addEventSubscriber(new FormExitSubscriber('ownermanager', $options));

        $builder->add(
            'name',
            TextType::class,
            [
                'label'      => 'mautic.core.name',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
            ]
        );

        $builder->add(
            'description',
            TextareaType::class,
            [
                'label'      => 'mautic.core.description',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control editor'],
                'required'   => false,
            ]
        );

        $builder->add(
            'type',
            ChoiceType::class,
            [
                'choices'           => $options['ownermanagerActions']['choices'],
                'placeholder'       => '',
                'label'             => 'mautic.ownermanager.form.type',
                'label_attr'        => ['class' => 'control-label'],
                'attr'              => [
                    'class'    => 'form-control',
                    'onchange' => 'Mautic.getOwnerManagerPropertiesForm(this.value);',
                ],
            ]
        );

        $transformer = new IdToEntityModelTransformer(
            $this->entityManager,
            'MauticUserBundle:User'
        );

        $builder->add(
            $builder->create(
                'owner',
                UserListType::class,
                [
                    'label'      => 'mautic.ownermanager.action.owner',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class' => 'form-control',
                    ],
                    'required'   => true,
                    'multiple'   => false,
                ]
            )
                ->addModelTransformer($transformer)
        );

        $type = (!empty($options['actionType'])) ? $options['actionType'] : $options['data']->getType();
        if ($type) {
            $formType   = (!empty($options['ownermanagerActions']['actions'][$type]['formType'])) ?
                $options['ownermanagerActions']['actions'][$type]['formType'] : 'genericownermanager_settings';
            $properties = ($options['data']) ? $options['data']->getProperties() : [];
            $builder->add(
                'properties',
                $formType,
                [
                    'label' => false,
                    'data'  => $properties,
                ]
            );
        }

        $builder->add(
            'triggerExisting',
            YesNoButtonGroupType::class,
            [
                'label'  => 'mautic.ownermanager.form.trigger.existing',
                'mapped' => false,
                'data'   => false,
            ]
        );

        if (!empty($options['data']) && $options['data'] instanceof OwnerManager) {
            $readonly = !$this->security->hasEntityAccess(
                'ownermanager:ownermanager:publishown',
                'ownermanager:ownermanager:publishother',
                $options['data']->getCreatedBy()
            );

            $data = $options['data']->isPublished(false);
        } elseif (!$this->security->isGranted('ownermanager:ownermanager:publishown')) {
            $readonly = true;
            $data     = false;
        } else {
            $readonly = false;
            $data     = true;
        }

        $builder->add(
            'isPublished',
            YesNoButtonGroupType::class,
            [
                'attr' => ['readonly' => $readonly],
                'data' => $data,
            ]
        );

        $builder->add(
            'publishUp',
            DateTimeType::class,
            [
                'widget'     => 'single_text',
                'label'      => 'mautic.core.form.publishup',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'       => 'form-control',
                    'data-toggle' => 'datetime',
                ],
                'format'     => 'yyyy-MM-dd HH:mm',
                'required'   => false,
            ]
        );

        $builder->add(
            'publishDown',
            DateTimeType::class,
            [
                'widget'     => 'single_text',
                'label'      => 'mautic.core.form.publishdown',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'       => 'form-control',
                    'data-toggle' => 'datetime',
                ],
                'format'     => 'yyyy-MM-dd HH:mm',
                'required'   => false,
            ]
        );

        //add category
        $builder->add(
            'category',
            CategoryListType::class,
            [
                'bundle' => 'ownermanager',
            ]
        );

        $builder->add('buttons', FormButtonsType::class);

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(['ownermanagerActions']);

        $resolver->setDefaults(
            [
                'data_class'      => OwnerManager::class,
                'actionType'      => '',
                'type'            => '',
                'formType'        => GenericOwnerManagerSettingsType::class,
                'formTypeOptions' => [],
            ]
        );
    }
}
