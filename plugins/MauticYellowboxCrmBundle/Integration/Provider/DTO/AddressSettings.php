<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticYellowboxCrmBundle\Integration\Provider\DTO;

use MauticPlugin\MauticYellowboxCrmBundle\Enum\SettingsKeyEnum;

class AddressSettings
{
    private static $addressFields = [
        'line1'      => 'Address 1',
        'line2'      => 'Address 2',
        'city'       => 'City',
        'postalCode' => 'Postal Code',
        'country'    => 'Country',
    ];

    /**
     * @var array
     */
    private $objects;

    /**
     * @var string
     */
    private $type;

    public function __construct(array $settings)
    {
        $this->objects = $settings['sync']['integration'][SettingsKeyEnum::SYNC_ADDRESS_OBJECTS] ?? [];
        $this->type    = $settings['sync']['integration'][SettingsKeyEnum::SYNC_ADDRESS_TYPE] ?? '';
    }

    /**
     * @return string[]
     */
    public static function getAddressFields()
    {
        return self::$addressFields;
    }

    /**
     * @param string $object
     *
     * @return bool
     */
    public function isEnabled($object)
    {
        return $this->type && in_array($object, $this->objects);
    }

    /**
     * @return array
     */
    public function getObjects()
    {
        return $this->objects;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function getFieldsToSync()
    {
        $fields = [];
        foreach (self::getAddressFields() as $name=>$label) {
            $field            = new \stdClass();
            $field->dbName    = $name;
            $field->name      = $label;
            $field->fieldType = 'ALPHANUMERIQUE';
            $field->id        = $name;
            $fields[]         = $field;
        }

        return $fields;
    }
}
