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

namespace MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\Validator;

use MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidObjectException;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\BaseModel;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\Lead;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository\LeadRepository;

class LeadValidator implements ObjectValidatorInterface
{
    /**
     * @var LeadRepository
     */
    private $leadRepository;

    /**
     * @var GeneralValidator
     */
    private $generalValidator;

    public function __construct(LeadRepository $leadRepository, GeneralValidator $generalValidator)
    {
        $this->leadRepository   = $leadRepository;
        $this->generalValidator = $generalValidator;
    }

    /**
     * @throws \Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidObjectException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidQueryArgumentException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\Validation\InvalidObject
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\YellowboxPluginException
     */
    public function validate(BaseModel $object): void
    {
        if (!$object instanceof Lead) {
            throw new \InvalidArgumentException('$object must be instance of Lead');
        }

        $description = $this->leadRepository->describe()->getFields();
        $this->generalValidator->validateObject($object, $description);
    }

    /**
     * @throws InvalidObjectException
     * @throws \Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidQueryArgumentException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\YellowboxPluginException
     */
    public function validateRequiredField(string $field, ?string $value)
    {
        $requiredFields = $this->leadRepository->getRequiredFields();
        if (isset($requiredFields[$field]) && (is_null($value) || 0 === strlen(trim($value)))) {
            throw new InvalidObjectException(sprintf('Field %s is required', $field));
        }
    }
}
