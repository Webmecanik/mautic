<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticYellowboxCrmBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException;
use Mautic\UserBundle\Form\Type\RoleListType;
use MauticPlugin\MauticYellowboxCrmBundle\Enum\SettingsKeyEnum;
use MauticPlugin\MauticYellowboxCrmBundle\Sync\AccountDataExchange;
use MauticPlugin\MauticYellowboxCrmBundle\Sync\ContactDataExchange;
use MauticPlugin\MauticYellowboxCrmBundle\Sync\LeadDataExchange;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository\UserRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ConfigSyncFeaturesType extends AbstractType
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * ConfigSyncFeaturesType constructor.
     */
    public function __construct(UserRepository $userRepository, CoreParametersHelper $coreParametersHelper)
    {
        $this->userRepository       = $userRepository;
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidQueryArgumentException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\YellowboxPluginException
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            SettingsKeyEnum::OWNER,
            ChoiceType::class,
            [
                'choices'    => $this->getFormOwners(),
                'label'      => 'mautic.plugin.yellowbox.form.owner',
                'label_attr' => [
                    'class' => 'control-label',
                ],
                'multiple'   => false,
                'required'   => true,
            ]
        );

        $builder->add(
            SettingsKeyEnum::OWNER_UPDATE_IS_ENABLED,
            ChoiceType::class,
            [
                'choices'     => [
                    'mautic.plugin.yellowbox.updateOwner' => 'updateOwner',
                ],
                'expanded'    => true,
                'multiple'    => true,
                'label'       => 'mautic.plugin.yellowbox.form.updateOwner',
                'label_attr'  => ['class' => 'control-label'],
                'placeholder' => false,
                'required'    => false,
            ]
        );

        $builder->add(
            SettingsKeyEnum::OWNER_MAUTIC_UPDATE_IS_ENABLED,
            YesNoButtonGroupType::class,
            [
                'label' => 'mautic.plugin.yellowbox.form.updateMauticOwner',
                'attr'  => [
                    'tooltip' => 'mautic.plugin.yellowbox.form.updateMauticOwner.tooltip',
                ],
            ]
        );

        $builder->add(
            SettingsKeyEnum::OWNER_MAUTIC_UPDATE_USER_ROLE,
            RoleListType::class,
            [
                'label'      => 'mautic.plugin.yellowbox.updateMauticOwnerUserRole',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'data-show-on' => '{"integration_config_featureSettings_sync_integration_updateMauticOwner_1":"checked"}',
                ],
                'placeholder' => false,
            ]
        );

        $yellowboxAvailableObjects = $this->coreParametersHelper->getParameter('yellowboxAvailableObjects');
        $choices                   = [];

        if (in_array(LeadDataExchange::OBJECT_LABEL, $yellowboxAvailableObjects)) {
            $choices[SettingsKeyEnum::PUSH_MAUTIC_CONTACT_AS_LEAD] = 'mautic.plugin.yellowbox.form.push_mautic_contact_as_lead';
        }

        if (in_array(ContactDataExchange::OBJECT_LABEL, $yellowboxAvailableObjects)) {
            $choices[SettingsKeyEnum::PUSH_MAUTIC_CONTACT_AS_CONTACT] = 'mautic.plugin.yellowbox.form.push_mautic_contact_as_contact';
        }

        $builder->add(
            SettingsKeyEnum::PUSH_MAUTIC_CONTACT_AS,
            ChoiceType::class,
            [
                'choices'    => array_flip($choices),
                'label'      => 'mautic.plugin.yellowbox.form.push_mautic_contact_as',
                'label_attr' => [
                    'class' => 'control-label',
                ],
                'multiple'   => false,
                'required'   => true,
            ]
        );

        $choices = [
            LeadDataExchange::OBJECT_NAME       => LeadDataExchange::OBJECT_LABEL,
            ContactDataExchange::OBJECT_NAME    => ContactDataExchange::OBJECT_LABEL,
            AccountDataExchange::OBJECT_NAME    => AccountDataExchange::OBJECT_LABEL,
        ];

        $choices = array_filter($choices, function ($value) {
            return in_array($value, $this->coreParametersHelper->getParameter('yellowboxAvailableObjects'));
        });

        $builder->add(
            SettingsKeyEnum::SYNC_ADDRESS_OBJECTS,
            ChoiceType::class,
            [
                'choices'    => array_flip($choices),
                'label'      => 'mautic.plugin.yellowbox.form.sync_address',
                'label_attr' => [
                    'class' => 'control-label',
                ],
                'multiple'   => true,
                'required'   => false,
            ]
        );

        $builder->add(
            SettingsKeyEnum::SYNC_ADDRESS_TYPE,
            TextType::class,
            [
                'label'      => 'mautic.plugin.yellowbox.form.sync_address_type',
                'label_attr' => ['class' => 'control-label'],
                'required'   => true,
                'attr'       => [
                    'class' => 'form-control',
                ],
            ]
        );

        /*
        Uncomment feature it YellowboxConfigProvider::getSupportedFeatures too
        Revert changes in YellowboxSettingProvider::isActivitySyncEnabled and YellowboxSettingProvider::getActivityEvents

        $builder->add(
            SettingsKeyEnum::ACTIVITY_EVENTS,
            ActivityListType::class
        );
        */
    }

    /**
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidQueryArgumentException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\YellowboxPluginException
     */
    private function getFormOwners(): array
    {
        try {
            $owners = $this->userRepository->findBy();
        } catch (PluginNotConfiguredException $e) {
            return [];
        }
        $ownersArray = [];
        foreach ($owners as $owner) {
            $ownersArray[(string) $owner] = $owner->getId();
        }

        return $ownersArray;
    }
}
