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
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\Account;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\BaseModel;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository\AccountRepository;

class AccountValidator implements ObjectValidatorInterface
{
    /**
     * @var AccountRepository
     */
    private $accountRepository;

    /**
     * @var GeneralValidator
     */
    private $generalValidator;

    public function __construct(AccountRepository $accountRepository, GeneralValidator $generalValidator)
    {
        $this->accountRepository = $accountRepository;
        $this->generalValidator  = $generalValidator;
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
        if (!$object instanceof Account) {
            throw new \InvalidArgumentException('$object must be instance of Account');
        }

        $description = $this->accountRepository->describe()->getFields();
        $this->generalValidator->validateObject($object, $description);
    }

    public function validateRequiredField(string $field, ?string $value)
    {
        $requiredFields = $this->accountRepository->getRequiredFields();
        if (isset($requiredFields[$field]) && (is_null($value) || 0 === strlen(trim($value)))) {
            throw new InvalidObjectException(sprintf('Field %s is required', $field));
        }
    }
}
