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
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\Event;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository\Direction\FieldDirectionInterface;

class EventRepository extends BaseRepository
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
    public function create(Event $module): Event
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
    public function retrieve(string $id): Event
    {
        return $this->findOneBy(['id' =>$id]);
    }

    /**
     * @param $contactId
     *
     * @return array|Event[]
     *
     * @throws \Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidQueryArgumentException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\YellowboxPluginException
     */
    public function findByContactId($contactId): array
    {
        return $this->findBy(['contact_id'=>(string) $contactId]);
    }

    /**
     * @return array|Event[]
     *
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidQueryArgumentException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\YellowboxPluginException
     * @throws \Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException
     */
    public function findByContactIds(array $contactIds): array
    {
        $moduleName = $this->getModuleFromRepositoryName();

        $query = 'select * from '.$moduleName;
        $query .= sprintf(" where contact_id in ('%s')", join("','", $contactIds));

        $return = [];

        $offset = 0;
        $limit  = 100;

        do {
            $queryLimiter = sprintf('LIMIT %d,%d', $offset, $limit);
            $result       = $this->connection->get('query', ['query' => $query.' '.$queryLimiter]);
            foreach ($result as $moduleObject) {
                $return[] = $this->getModel((array) $moduleObject);
            }
            $offset += $limit;
        } while (count($result));

        return $return;
    }

    public function getModuleFromRepositoryName(): string
    {
        return CacheEnum::EVENT;
    }

    protected function getModel(array $objectData): Event
    {
        return $this->modelFactory->createEvent($objectData);
    }

    /**
     * @throws \Exception
     */
    protected function getFieldDirection(): FieldDirectionInterface
    {
        throw new \Exception('Events has no Fields');
    }
}
