<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDivaltoBundle\Sync\Mapping\Field;

use Mautic\CoreBundle\Helper\ParamsLoaderHelper;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\ObjectMappingDAO;

class Field
{
    /**
     * @var array
     */
    private static $parameters = [];

    private $name;
    private $label;
    private $dataType;
    private $isRequired;
    private $isWritable;
    private $isWritableOnMautic;

    public function __construct(array $field = [], string $objectName = '')
    {
        $this->name               = $field['fieldName'] ?? '';
        $this->label              = $this->getLabelFromField($field);
        $this->dataType           = $field['fieldType'] ?? 'text';
        if ('customer' === $objectName) {
            $this->isRequired         = in_array($this->name, ['name']);
        } else {
            $this->isRequired         = in_array($this->name, ['email']);
        }
        $this->isWritable         = (bool) !in_array($this->dataType, ['text(foreignkey)']);
        $this->isWritableOnMautic = ('marketinginbound' !== $objectName);
    }

    private function getLabelFromField(array $field): string
    {
        $locale        = self::getParameter('locale');
        $languageCodes = array_column($field['translations'], 'languageCode');

        $index         = array_search('EN', $languageCodes);

        if (0 === strpos($locale, 'fr')) {
            $index = array_search('FR', $languageCodes);
        } elseif (0 === strpos($locale, 'de')) {
            $index = array_search('DE', $languageCodes);
        } elseif (0 === strpos($locale, 'es')) {
            $index = array_search('ES', $languageCodes);
        } elseif (0 === strpos($locale, 'it')) {
            $index = array_search('IT', $languageCodes);
        } elseif (0 === strpos($locale, 'pt')) {
            $index = array_search('PT', $languageCodes);
        }

        return $field['translations'][$index]['text'] ?? $this->name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getDataType(): string
    {
        return $this->dataType;
    }

    public function isRequired(): bool
    {
        return $this->isRequired;
    }

    public function isWritable(): bool
    {
        return $this->isWritable;
    }

    public function getSupportedSyncDirection(): string
    {
        if ($this->isWritableOnMautic && $this->isWritable) {
            return ObjectMappingDAO::SYNC_BIDIRECTIONALLY;
        } elseif ($this->isWritable) {
            return ObjectMappingDAO::SYNC_TO_INTEGRATION;
        } elseif ($this->isWritableOnMautic) {
            return ObjectMappingDAO::SYNC_TO_MAUTIC;
        }
    }

    public function isWritableOnMautic()
    {
        return $this->isWritableOnMautic;
    }

    /**
     * @param string $parameter
     *
     * @return mixed
     */
    private static function getParameter($parameter)
    {
        if (empty(self::$parameters)) {
            self::$parameters = (new ParamsLoaderHelper())->getParameters();
        }

        return self::$parameters[$parameter];
    }
}
