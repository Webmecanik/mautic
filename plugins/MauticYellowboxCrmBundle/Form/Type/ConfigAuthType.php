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

use Mautic\IntegrationsBundle\Form\Type\NotBlankIfPublishedConstraintTrait;
use MauticPlugin\MauticYellowboxCrmBundle\Integration\Provider\YellowboxConfigProvider;
use MauticPlugin\MauticYellowboxCrmBundle\Validator\Constraints\Connection as ConnectionConstraint;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Connection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigAuthType extends AbstractType
{
    use NotBlankIfPublishedConstraintTrait;

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $password = null;

        /** @var YellowboxConfigProvider $configProvider */
        $configProvider = $options['integration'];
        if ($configProvider->getIntegrationConfiguration() && $configProvider->getIntegrationConfiguration()->getApiKeys()) {
            $password = $configProvider->getIntegrationConfiguration()->getApiKeys()['password'] ?? null;
        }

        $builder->add(
            'url',
            UrlType::class,
            [
                'label'      => 'mautic.yellowbox.form.url',
                'label_attr' => ['class' => 'control-label'],
                'required'   => true,
                'attr'       => [
                    'class' => 'form-control',
                ],
                'constraints' => [
                    $this->getNotBlankConstraint(),
                    new ConnectionConstraint($this->connection),
                ],
            ]
        );

        $builder->add(
            'username',
            TextType::class,
            [
                'label'      => 'mautic.yellowbox.form.username',
                'label_attr' => ['class' => 'control-label'],
                'required'   => true,
                'attr'       => [
                    'class' => 'form-control',
                ],
                'constraints' => [$this->getNotBlankConstraint()],
            ]
        );

        $builder->add(
            'password',
            PasswordType::class,
            [
                'label'      => 'mautic.yellowbox.form.password',
                'label_attr' => ['class' => 'control-label'],
                'required'   => true,
                'attr'       => [
                    'class'        => 'form-control',
                    'placeholder'  => '**************',
                    'autocomplete' => 'off',
                ],
                'constraints' => [$this->getNotBlankConstraint()],
                'empty_data'  => $password,
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
           'integration' => null,
        ]);
    }
}
