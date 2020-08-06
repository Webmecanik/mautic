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
use MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidObjectValueException;
use MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidQueryArgumentException;
use MauticPlugin\MauticYellowboxCrmBundle\Exceptions\NormalizeEmptyException;

/**
 * Trait TransformationsTrait.
 */
trait TransformationsTrait
{
    private $transformations = [
        // yellow box field types
        TransformerInterface::TELEPHONE            => [
            'func' => 'transformPhone',
        ],
        TransformerInterface::ZONETEXTE => [
            'func' => 'transformString',
        ],
         TransformerInterface::IDUTILISATEUR=> [
            'func' => 'transformOwner',
        ],
        TransformerInterface::BOOLEEN          => [
            'func' => 'transformBoolean',
        ],
        TransformerInterface::ENTIER          => [
            'func' => 'transformInt',
        ],
        TransformerInterface::NUMERIQUE          => [
            'func' => 'transformDouble',
        ],
        TransformerInterface::DATE          => [
            'func' => 'transformDatetime',
        ],
        TransformerInterface::ALPHANUMERIQUE          => [
            'func' => 'transformString',
        ],
        NormalizedValueDAO::EMAIL_TYPE            => [
            'func' => 'transformEmail',
        ],
        NormalizedValueDAO::STRING_TYPE           => [
            'func' => 'transformString',
        ],
        NormalizedValueDAO::PHONE_TYPE            => [
            'func' => 'transformPhone',
        ],
        NormalizedValueDAO::BOOLEAN_TYPE          => [
            'func' => 'transformBoolean',
        ],
        TransformerInterface::DNC_TYPE            => [
            'func' => 'transformDNC',
        ],
        NormalizedValueDAO::INT_TYPE              => [
            'func' => 'transformInt',
        ],
        NormalizedValueDAO::DATE_TYPE             => [
            'func' => 'transformDate',
        ],
        NormalizedValueDAO::DOUBLE_TYPE           => [
            'func' => 'transformDouble',
        ],
        NormalizedValueDAO::TEXT_TYPE             => [
            'func' => 'transformString',
        ],
        NormalizedValueDAO::DATETIME_TYPE         => [
            'func' => 'transformDatetime',
        ],
    ];

    /**
     * @param       $typeName
     * @param mixed $value
     *
     * @throws InvalidObjectValueException
     * @throws InvalidQueryArgumentException
     */
    public function transform($typeName, $value): NormalizedValueDAO
    {
        if (!isset($this->transformations[$typeName])) {
            throw new InvalidQueryArgumentException(sprintf('Unknown type "%s", cannot transform. Value type: %s', $typeName, var_export($value, true)));
        }

        $transformationMethod = $this->transformations[$typeName]['func'];
        try {
            $transformedValue     = $this->$transformationMethod($value);
        } catch (NormalizeEmptyException $exception) {
            $value = $transformedValue = $exception->getValue();
        }

        if (
            is_null($transformedValue)
            && isset($this->transformations['func']['required'])
            && $this->transformations['func']['required']
        ) {
            throw new InvalidObjectValueException('Required property has null value', $transformedValue, $typeName);
        }

        return new NormalizedValueDAO($typeName, $value, $transformedValue);
    }

    protected function transformEmail(?string $value): ?string
    {
        if (is_null($value) || 0 === strlen(trim($value))) {
            return null;
        }

        return $this->transformString($value);
    }

    /**
     * @param $value
     */
    protected function transformString($value): ?string
    {
        if (is_null($value)) {
            return $value;
        }

        return (string) $value;
    }

    /**
     * @param $value
     */
    protected function transformBoolean($value): ?int
    {
        if (is_null($value)) {
            return $value;
        }

        return intval((bool) $value);
    }

    /**
     * @param $value
     */
    protected function transformPhone($value): ?string
    {
        return $this->transformString($value);
    }

    /**
     * @param \DateTimeInterface|string $value
     */
    protected function transformDate($value): ?string
    {
        if ('0002-11-30 00:00:00.0' == $value) {
            throw new NormalizeEmptyException();
        }

        return ($value instanceof \DateTimeInterface) ? $value->format('Y-m-d') : $this->transformString($value);
    }

    /**
     * @param $value
     */
    protected function transformInt($value): ?int
    {
        return intval($value);
    }

    /**
     * @param $value
     *
     * @return string
     */
    protected function transformCurrency($value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        return number_format(floatval($value), 2);
    }

    /**
     * @param $value
     */
    protected function transformDouble($value): float
    {
        return doubleval($value);
    }

    /**
     * @param $value
     */
    protected function transformSkype($value): string
    {
        return $this->transformString((string) $value);
    }

    /**
     * @param \DateTimeInterface|string $time
     *
     * @return string|null
     */
    protected function transformTime($time): string
    {
        return $time instanceof \DateTimeInterface ? $time->format('H:i:s') : $this->transformString($time);
    }

    /**
     * @param \DateTimeInterface|string $time
     */
    protected function transformDatetime($time): ?string
    {
        if ('0002-11-30 00:00:00.0' == $time) {
            throw new NormalizeEmptyException();
        }

        return $time instanceof \DateTimeInterface ? $time->format('Y-m-d H:i:s') : $this->transformString($time);
    }
}
