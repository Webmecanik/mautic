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

namespace MauticPlugin\MauticYellowboxCrmBundle\Sync\ValueNormalizer\Transformers;

use Mautic\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use Mautic\LeadBundle\Entity\DoNotContact;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\ModuleFieldInfo;

final class MauticYellowboxTransformer implements TransformerInterface
{
    use TransformationsTrait {
        transform as protected commonTransform;
    }

    /**
     * @var ModuleFieldInfo
     */
    private $currentFieldInfo;

    /**
     * @param $yellowboxValue
     *
     * @return int
     */
    protected function transformDNC($yellowboxValue)
    {
        return $yellowboxValue ? DoNotContact::UNSUBSCRIBED : DoNotContact::IS_CONTACTABLE;
    }

    /**
     * @param $fieldInfo
     * @param mixed $value
     *
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidObjectValueException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidQueryArgumentException
     */
    public function transform($fieldInfo, $value): NormalizedValueDAO
    {
        $this->setCurrentFieldInfo($fieldInfo);

        return $this->commonTransform($this->getCurrentFieldInfo()->getType()->getName(), $value);
    }

    public function getCurrentFieldInfo(): ModuleFieldInfo
    {
        return $this->currentFieldInfo;
    }

    public function setCurrentFieldInfo(ModuleFieldInfo $currentFieldInfo): MauticYellowboxTransformer
    {
        $this->currentFieldInfo = $currentFieldInfo;

        return $this;
    }

    /**
     * @param $value
     */
    protected function transformDate($value): ?string
    {
        return is_null($value) ? null : $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : (string) $value;
    }
}
