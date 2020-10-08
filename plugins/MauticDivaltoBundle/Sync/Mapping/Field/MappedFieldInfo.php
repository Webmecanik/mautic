<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDivaltoBundle\Sync\Mapping\Field;

use Mautic\IntegrationsBundle\Mapping\MappedFieldInfoInterface;

class MappedFieldInfo implements MappedFieldInfoInterface
{
    private $field;

    public function __construct(Field $field)
    {
        $this->field = $field;
    }

    public function getName(): string
    {
        return $this->field->getName();
    }

    public function getLabel(): string
    {
        return $this->field->getLabel();
    }

    public function showAsRequired(): bool
    {
        return $this->field->isRequired();
    }

    public function hasTooltip(): bool
    {
        return false;
    }

    public function getTooltip(): string
    {
        return '';
    }

    public function isBidirectionalSyncEnabled(): bool
    {
        return $this->field->isWritable() && $this->field->isWritableOnMautic();
    }

    public function isToIntegrationSyncEnabled(): bool
    {
        return $this->field->isWritable();
    }

    public function isToMauticSyncEnabled(): bool
    {
        return $this->field->isWritableOnMautic();
    }
}
