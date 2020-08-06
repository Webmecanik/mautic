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

namespace MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository;

use MauticPlugin\MauticYellowboxCrmBundle\Enum\CacheEnum;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\User;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository\Direction\FieldDirectionInterface;

/**
 * Class UserRepository.
 */
class UserRepository extends BaseRepository
{
    /**
     * @throws \Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidQueryArgumentException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\YellowboxPluginException
     */
    public function create(User $module): User
    {
        return $this->createUnified($module);
    }

    /**
     * @throws \Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidQueryArgumentException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\YellowboxPluginException
     */
    public function retrieve(string $id): User
    {
        return $this->findOneBy(['id' =>$id]);
    }

    public function getModuleFromRepositoryName(): string
    {
        return CacheEnum::USER;
    }

    protected function getModel(array $objectData): User
    {
        return $this->modelFactory->createUser($objectData);
    }

    /**
     * @throws \Exception
     */
    protected function getFieldDirection(): FieldDirectionInterface
    {
        throw new \Exception('User has no Fields');
    }
}
