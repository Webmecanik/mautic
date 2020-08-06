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
use MauticPlugin\MauticYellowboxCrmBundle\Exceptions\CachedItemNotFoundException;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\Account;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository\Direction\FieldDirectionInterface;

class AccountRepository extends BaseRepository
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
    public function create(Account $module): Account
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
    public function retrieve(string $id): Account
    {
        return $this->findOneBy(['id' =>$id]);
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
    public function getByContactId(string $contactId): array
    {
        return $this->findBy(['contact_id' => $contactId]);
    }

    /**
     * @param array  $where
     * @param string $columns
     *
     * @return array|Account[]
     *
     * @throws \Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidQueryArgumentException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\YellowboxPluginException
     */
    public function findBy($where = [], $columns = '*'): array
    {
        if (count($where)) {
            return $this->findByInternal($where, $columns);
        }

        $columnsString = is_array($columns) ? join('|', $columns) : $columns;
        $key           = 'yellowboxcrm_acccounts_'.sha1($columnsString);
        try {
            return $this->fieldCache->getAccountQuery($key);
        } catch (CachedItemNotFoundException $e) {
        }

        $result = $this->findByInternal($where, $columns);
        $this->fieldCache->setAccountQuery($key, $result);

        return $result;
    }

    public function getModuleFromRepositoryName(): string
    {
        return CacheEnum::ACCOUNT;
    }

    protected function getModel(array $objectData): Account
    {
        return $this->modelFactory->createAccount($objectData);
    }

    protected function getFieldDirection(): FieldDirectionInterface
    {
        return $this->fieldDirectionFactory->getAccountFieldDirection();
    }
}
