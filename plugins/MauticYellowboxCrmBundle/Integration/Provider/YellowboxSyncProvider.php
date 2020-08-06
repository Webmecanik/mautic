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

namespace MauticPlugin\MauticYellowboxCrmBundle\Integration\Provider;

use Mautic\IntegrationsBundle\Integration\ConfigurationTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\SyncInterface;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\SyncDataExchangeInterface;
use MauticPlugin\MauticYellowboxCrmBundle\Integration\BasicTrait;
use MauticPlugin\MauticYellowboxCrmBundle\Sync\DataExchange;

class YellowboxSyncProvider implements SyncInterface
{
    use BasicTrait;
    use ConfigurationTrait;

    /**
     * @var DataExchange
     */
    private $dataExchange;

    /**
     * YellowboxSyncProvider constructor.
     */
    public function __construct(DataExchange $dataExchange)
    {
        $this->dataExchange = $dataExchange;
    }

    /**
     * @throws \Mautic\IntegrationsBundle\Sync\Exception\ObjectNotSupportedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidQueryArgumentException
     */
    public function getMappingManual(): MappingManualDAO
    {
        return $this->dataExchange->getFieldMapper()->getObjectsMappingManual();
    }

    public function getSyncDataExchange(): SyncDataExchangeInterface
    {
        return $this->dataExchange;
    }
}
