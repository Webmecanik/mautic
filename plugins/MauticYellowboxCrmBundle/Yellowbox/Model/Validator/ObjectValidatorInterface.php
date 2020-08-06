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

namespace MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\Validator;

use MauticPlugin\MauticYellowboxCrmBundle\Exceptions\Validation\InvalidObject;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\BaseModel;

interface ObjectValidatorInterface
{
    /**
     * @throws InvalidObject
     */
    public function validate(BaseModel $object): void;
}
