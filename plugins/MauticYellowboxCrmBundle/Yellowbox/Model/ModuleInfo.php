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

namespace MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model;

use MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidObjectException;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository\Direction\FieldDirectionInterface;

class ModuleInfo
{
    /**
     * @var array|ModuleFieldInfo[]
     */
    private $fields;

    public function __construct(array $data, FieldDirectionInterface $fieldDirection)
    {
        foreach ($data as $fieldInfo) {
            $this->fields[$fieldInfo->dbName] = new ModuleFieldInfo($fieldInfo, $fieldDirection);
        }
    }

    /**
     * @return array|ModuleFieldInfo[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @throws InvalidObjectException
     */
    public function getField(string $fieldName): ModuleFieldInfo
    {
        if (!isset($this->fields[$fieldName])) {
            throw new InvalidObjectException('Unknown field info requested: '.$fieldName);
        }

        return $this->fields[$fieldName];
    }
}
