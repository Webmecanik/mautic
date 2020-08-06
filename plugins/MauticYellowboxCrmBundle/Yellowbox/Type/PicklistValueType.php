<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc. Jan Kozak <galvani78@gmail.com>
 *
 * @link        http://mautic.com
 * @created     29.10.18
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Type;

/**
 * Class MultipicklistType.
 *
 * @see
 */
class PicklistValueType
{
    /** @var string */
    private $label;

    /** @var string */
    private $value;

    public function __construct(\stdClass $description)
    {
        $this->value = $description->value;
        $this->label = $description->label;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): PicklistValueType
    {
        $this->label = $label;

        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): PicklistValueType
    {
        $this->value = $value;

        return $this;
    }
}
