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

namespace MauticPlugin\MauticYellowboxCrmBundle\Sync\ValueNormalizer;

use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\FieldDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use Mautic\IntegrationsBundle\Sync\ValueNormalizer\ValueNormalizerInterface;
use MauticPlugin\MauticYellowboxCrmBundle\Sync\ValueNormalizer\Transformers\MauticYellowboxTransformer;
use MauticPlugin\MauticYellowboxCrmBundle\Sync\ValueNormalizer\Transformers\YellowboxMauticTransformer;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\ModuleFieldInfo;

/**
 * Class ValueNormalizer.
 */
final class YellowboxValueNormalizer implements ValueNormalizerInterface
{
    /**
     * @var YellowboxMauticTransformer
     */
    private $v2mTransformer;

    /**
     * @var MauticYellowboxTransformer
     */
    private $m2vTransformer;

    /**
     * YellowboxValueNormalizer constructor.
     */
    public function __construct(YellowboxMauticTransformer $v2mTransformer, MauticYellowboxTransformer $m2vTransformer)
    {
        $this->v2mTransformer = $v2mTransformer;
        $this->m2vTransformer = $m2vTransformer;
    }

    /**
     * @param $value
     *
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidObjectValueException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidQueryArgumentException
     */
    public function normalizeForMautic(string $type, $value): NormalizedValueDAO
    {
        return $this->v2mTransformer->transform($type, $value);
    }

    /**
     * @param $value
     *
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidObjectValueException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidQueryArgumentException
     */
    public function normalizeForMauticTyped(ModuleFieldInfo $fieldInfo, $value): NormalizedValueDAO
    {
        return $this->v2mTransformer->transformTyped($fieldInfo, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function normalizeForIntegration(NormalizedValueDAO $value)
    {
        throw new \Exception('Use normalizeForYellowbox instead');
    }

    /**
     * @return mixed
     *
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidObjectValueException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidQueryArgumentException
     */
    public function normalizeForYellowbox(ModuleFieldInfo $fieldInfo, FieldDAO $fieldDAO)
    {
        return $this->m2vTransformer->transform($fieldInfo, $fieldDAO->getValue()->getOriginalValue())->getNormalizedValue();
    }

    /**
     * @param $id
     */
    public static function getTableNameWithoutPrefix($id, string $name): string
    {
        return str_ireplace('c'.$id, '', $name);
    }

    public static function getArrayFromJsonObject(\stdClass $object): array
    {
        return json_decode(json_encode($object), true);
    }
}
