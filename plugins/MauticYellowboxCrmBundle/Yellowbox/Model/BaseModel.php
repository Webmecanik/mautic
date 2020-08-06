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

use MauticPlugin\MauticYellowboxCrmBundle\Integration\Provider\DTO\AddressSettings;
use MauticPlugin\MauticYellowboxCrmBundle\Sync\ValueNormalizer\YellowboxValueNormalizer;

abstract class BaseModel
{
    /** @var array */
    protected $data = [];

    /** @var array */
    private $dataNormalize;           //  This contains the real data of the object for manipulation

    public function __construct(array $data = null)
    {
        if (!is_null($data)) {
            $this->hydrate($data);
        }
    }

    public function hydrate(array $attributes): void
    {
        if (isset($attributes['values'])) {
            foreach ($attributes['values'] as $object) {
                $this->data[$object->field->dbName] = $object->value;
                $this->dataNormalize[YellowboxValueNormalizer::getTableNameWithoutPrefix(
                    $object->field->id,
                    $object->field->dbName
                )]                                  = $object->value;
            }
        } else {
            foreach ($attributes as $attribute => $value) {
                $this->data[$attribute] = $value;
            }
        }
    }

    /**
     * @param array $fields
     */
    public function dehydrate($fields = []): array
    {
        if (0 === count($fields)) {
            return $this->data;
        }

        $response = [];

        foreach ($fields as $fieldName) {
            $response[$fieldName] = $this->data[$fieldName] ?? null;
        }

        return $response;
    }

    /**
     * @param array $fields
     *
     * @return array
     */
    public function dehydrateAddress($fields = [])
    {
        $addressFields = AddressSettings::getAddressFields();

        $response = [];

        foreach ($this->dehydrate($fields) as $addressField=>$field) {
            if (array_key_exists($addressField, $addressFields)) {
                $response[$addressField] = $field;
            }
        }

        return $response;
    }

    public function getId(): ?string
    {
        return $this->data['ID_ELEMENT'] ?? $this->data['id'] ?? null;
    }

    public function getModifiedTime(): ?\DateTime
    {
        return isset($this->dataNormalize['MODIF_DATE']) ? new \DateTime($this->dataNormalize['MODIF_DATE']) : new \DateTime('');
    }

    /**
     * @param $identified
     * @param $value
     *
     * @return $this
     */
    public function set($identified, $value): self
    {
        $this->data[$identified] = $value;

        return $this;
    }

    public function append(array $data)
    {
        $this->data = array_merge($this->data, $data);
    }

    public function getData(): array
    {
        return $this->data;
    }
}
