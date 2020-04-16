<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class TimeFormatType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * TimeFormat constructor.
     *
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => [
                '24' => '24-'.$this->translator->trans('mautic.core.time.hour'),
                '12' => '12-'.$this->translator->trans('mautic.core.time.hour'),
            ],
            'expanded'    => false,
            'multiple'    => false,
            'label'       => 'mautic.core.type.time_format',
            'label_attr'  => ['class' => ''],
            'empty_value' => false,
            'required'    => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }
}
