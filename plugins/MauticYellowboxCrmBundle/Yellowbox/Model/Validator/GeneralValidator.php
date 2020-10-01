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
use MauticPlugin\MauticYellowboxCrmBundle\Exceptions\Validation\InvalidObject;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\BaseModel;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\ModuleFieldInfo;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\Validator\Constraints\Date;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\Validator\Constraints\MultiChoice;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository\UserRepository;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Type\CommonType;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Type\DateType;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Type\PicklistType;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Time;
use Symfony\Component\Validator\Validation;

class GeneralValidator
{
    /** @var UserRepository */
    private $userRepository;

    /** @var \Symfony\Component\Validator\ValidatorInterface */
    private $validator;

    /** @var array */
    private $existingUsersIds = [];

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
        $this->validator      = Validation::createValidator();
    }

    /**
     * @throws InvalidObject
     * @throws InvalidObjectException
     * @throws \Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidQueryArgumentException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\YellowboxPluginException
     */
    public function validateObject(BaseModel $object, array $description): void
    {
        foreach ($object->dehydrate() as $fieldName => $fieldValue) {
            if (isset($description[$fieldName])) {
                $fieldDescription = $description[$fieldName];
                $this->validateField($fieldDescription, $fieldValue);
            }
        }
    }

    /**
     * @param $fieldValue
     *
     * @throws InvalidObject
     * @throws InvalidObjectException
     * @throws \Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidQueryArgumentException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\YellowboxPluginException
     */
    private function validateField(ModuleFieldInfo $fieldInfo, $fieldValue): void
    {
        $validators = [];
        if (!$fieldInfo->isNullable() && $fieldInfo->isRequired() && null === $fieldValue) {
            $validators[] = new NotNull();
        }

        //  Validate by data type
        $validators = array_merge($validators, $this->getValidatorsForType($fieldInfo->getType(), $fieldValue));

        if (!count($validators)) {
            return;
        }

        //  Validate for required fields
        $violations = $this->validator->validate($fieldValue, $validators);
        if (!count($violations)) {
            return;
        }

        throw new InvalidObject($violations, $fieldInfo, $fieldValue);
    }

    /**
     * @param $fieldValue
     *
     * @throws InvalidObjectException
     * @throws \Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidQueryArgumentException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\YellowboxPluginException
     */
    private function getValidatorsForType(CommonType $typeObject, $fieldValue): array
    {
        $validators = [];

        switch ($typeObject->getName()) {
            case 'autogenerated':
            case 'string':
            case 'phone':
            case 'text':
            case 'double':
            case 'integer':
            case 'skype':
            case 'url':
                break;
            case 'email':
                $validators[] = new Email();
                break;
            case 'owner':
                if (!count($this->existingUsersIds)) {
                    $users                  = $this->userRepository->findBy();
                    $this->existingUsersIds = array_map(function (BaseModel $o) { return $o->getId(); }, $users);
                }

                $validators[] = new Choice(['choices' => $this->existingUsersIds]);
                break;
            case 'reference':
                break;
            case 'boolean':
                break;
            case 'picklist':
                /* @var PicklistType $typeObject */
                $validators[] = new Choice(['choices' => $typeObject->getPicklistValuesArray()]);
                break;
            case 'multipicklist':
                /* @var PicklistType $typeObject */
                $validators[] = new MultiChoice(['choices' => $typeObject->getPicklistValuesArray(), 'multiple' => true]);
                break;
            case 'date':
                /* @var DateType $typeObject */
                $validators[] = new Date(['format'=>$typeObject->getFormat()]);
                break;
            case 'currency':
                break;
            case 'time':
                $validators[] = new Time();
                break;
        }

        return $validators;
    }
}
