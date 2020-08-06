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

use Mautic\IntegrationsBundle\Mapping\MappedFieldInfoInterface;
use MauticPlugin\MauticYellowboxCrmBundle\Sync\ValueNormalizer\Transformers\TransformerInterface;
use MauticPlugin\MauticYellowboxCrmBundle\Sync\ValueNormalizer\YellowboxValueNormalizer;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository\Direction\FieldDirectionInterface;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Type\CommonType;

class ModuleFieldInfo implements MappedFieldInfoInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $label;

    /**
     * @var bool
     */
    private $required;

    /**
     * @var CommonType
     */
    private $type;

    /**
     * @var bool
     */
    private $isUnique;

    /**
     * @var bool
     */
    private $nullable;

    /**
     * @var bool
     */
    private $editable;

    /**
     * @var string
     */
    private $default;

    /**
     * @var FieldDirectionInterface
     */
    private $fieldDirection;

    /**
     * @var string
     */
    private $dbName;

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $normalizeName;

    /**
     * @param FieldDirectionInterface $fieldDirection
     */
    public function __construct(\stdClass $data, FieldDirectionInterface $fieldDirection = null)
    {
        $this->label          = strip_tags($data->name);
        $this->id             = (int) $data->id;
        $this->name           = $data->dbName;
        $this->normalizeName  = YellowboxValueNormalizer::getTableNameWithoutPrefix($this->id, $this->name);
        $this->nullable       = false;
        $this->editable       = defined(TransformerInterface::class.'::'.$data->fieldType);
        $this->default        = null;
        $this->type           = new CommonType($data);
        $this->fieldDirection = $fieldDirection;
        $this->setIsUnique();
        $this->setRequired();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function showAsRequired(): bool
    {
        return $this->isRequired();
    }

    /**
     * @return mixed
     */
    public function getTypeName()
    {
        return $this->getType()->getName();
    }

    public function getType(): CommonType
    {
        return $this->type;
    }

    /**
     * @return ModuleFieldInfo
     */
    public function setType(CommonType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function isUnique(): bool
    {
        return $this->isUnique;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function isEditable(): bool
    {
        return $this->editable;
    }

    public function getDefault(): string
    {
        return $this->default;
    }

    public function hasTooltip(): bool
    {
        return false;
    }

    /**
     * @throws \Exception
     */
    public function getTooltip(): string
    {
        throw new \Exception('This field has no tooltip');
    }

    public function isBidirectionalSyncEnabled(): bool
    {
        return $this->fieldDirection->isFieldReadable($this) && $this->fieldDirection->isFieldWritable($this);
    }

    public function isToIntegrationSyncEnabled(): bool
    {
        return $this->fieldDirection->isFieldWritable($this);
    }

    public function isToMauticSyncEnabled(): bool
    {
        return $this->fieldDirection->isFieldReadable($this);
    }

    private function setIsUnique(): void
    {
        $this->isUnique = $this->setRequired();
    }

    private function setRequired(): void
    {
        $this->required = $this->fieldDirection->isFieldRequired($this);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getNormalizeName(): string
    {
        return $this->normalizeName;
    }
}
