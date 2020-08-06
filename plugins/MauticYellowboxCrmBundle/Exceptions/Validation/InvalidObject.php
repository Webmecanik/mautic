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

namespace MauticPlugin\MauticYellowboxCrmBundle\Exceptions\Validation;

use MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidObjectException;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\ModuleFieldInfo;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class InvalidObject extends InvalidObjectException
{
    /**
     * InvalidObject constructor.
     *
     * @param $fieldValue
     */
    public function __construct(ConstraintViolationListInterface $violations, ModuleFieldInfo $fieldInfo, $fieldValue)
    {
        $violationsMessages = [];
        /** @var ConstraintViolation $violation */
        foreach ($violations as $violation) {
            $violationsMessages[] = $violation->getMessage();
        }

        $message = sprintf("Validation of %s failed. Field value: '%s'. %s",
            $fieldInfo->getName(),
            $fieldValue,
            join('. ', $violationsMessages)
            );

        parent::__construct($message);
    }
}
