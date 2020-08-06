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

use Mautic\EmailBundle\Exception\InvalidEmailException;
use Mautic\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use Mautic\LeadBundle\Entity\DoNotContact;
use MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidObjectValueException;
use MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SkipSyncException;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Helper\OwnerMauticSync;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\ModuleFieldInfo;

/**
 * Class YellowboxMauticTransformer.
 */
final class YellowboxMauticTransformer implements TransformerInterface
{
    use TransformationsTrait;

    /**
     * @var OwnerMauticSync
     */
    private $ownerMauticSync;

    public function __construct(OwnerMauticSync $ownerMauticSync)
    {
        $this->ownerMauticSync = $ownerMauticSync;
    }

    /**
     * @var ModuleFieldInfo
     */
    private $moduleFieldInfo;

    protected function transformDNC($mauticValue)
    {
        return $mauticValue ? DoNotContact::UNSUBSCRIBED : DoNotContact::IS_CONTACTABLE;
    }

    /**
     * @param $mauticValue
     *
     * @return string|null
     */
    protected function transformMultiPicklist($mauticValue)
    {
        if (is_null($mauticValue)) {
            return null;
        }
        $values = explode('|##|', $mauticValue);
        array_walk($values, function (&$element) {
            $element = trim($element);
        });

        return $this->transformString(join('|', $values));
    }

    /**
     * @param \DateTimeInterface $value
     */
    protected function transformDate($value): ?string
    {
        if (is_null($value) || '' === $value || '0000-00-00' === $value) {
            return null;
        }

        $dateObject = \DateTime::createFromFormat('Y-m-d', $value);

        return $dateObject->format('Y-m-d');
    }

    /**
     * @throws InvalidObjectValueException
     */
    protected function transformPicklist(string $value): ?string
    {
        if ('' === $value || is_null($value)) {
            return null;
        }

        $type       = $this->moduleFieldInfo->getType();
        $dictionary = $type->getPicklistValuesArray();

        if (!isset($dictionary[$value])) {
            throw new InvalidObjectValueException(sprintf('Invalid picklist value. Available: [%s]', join(',', array_keys($dictionary))), (string) $value, $type->getName());
        }

        return $this->transformString($value);
    }

    /**
     * @param $value
     *
     * @throws InvalidObjectValueException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidQueryArgumentException
     */
    public function transformTyped(ModuleFieldInfo $fieldInfo, $value): NormalizedValueDAO
    {
        $this->moduleFieldInfo = $fieldInfo;

        return $this->transform($fieldInfo->getTypeName(), $value);
    }

    /**
     * @param $value
     *
     * @return string|null
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidQueryArgumentException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\YellowboxPluginException
     */
    protected function transformOwner($value): ?int
    {
        $skipMessage = 'Skip sync owner for '.$value;

        if (is_null($value) || '' === $value) {
            throw new SkipSyncException($skipMessage);
        }

        try {
            $mauticUser = $this->ownerMauticSync->findOrCreateMauticUser($value);

            return $mauticUser->getId();
        } catch (InvalidEmailException $invalidEmailException) {
        }

        throw new SkipSyncException($skipMessage);
    }
}
