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

namespace MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository\Cache;

use Mautic\CoreBundle\Helper\CacheStorageHelper;
use MauticPlugin\MauticYellowboxCrmBundle\Enum\CacheEnum;
use MauticPlugin\MauticYellowboxCrmBundle\Exceptions\CachedItemNotFoundException;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\ModuleInfo;

class FieldCache
{
    /**
     * @var CacheStorageHelper
     */
    private $cacheStorageHelper;

    public function __construct(CacheStorageHelper $cacheStorageHelper)
    {
        $this->cacheStorageHelper = $cacheStorageHelper;
    }

    /**
     * @throws CachedItemNotFoundException
     */
    public function getModuleInfo(string $key): ModuleInfo
    {
        return $this->getFromCache($key);
    }

    public function setModuleInfo(string $key, ModuleInfo $moduleInfo): void
    {
        $this->saveToCache($key, $moduleInfo);
    }

    /**
     * @throws CachedItemNotFoundException
     */
    public function getUserQuery(string $key): array
    {
        return $this->getFromCache($key);
    }

    /**
     * @throws CachedItemNotFoundException
     */
    public function getAccountQuery(string $key): array
    {
        return $this->getFromCache($key);
    }

    public function setUserQuery(string $key, array $data): void
    {
        $this->saveToCache($key, $data);
    }

    public function setAccountQuery(string $key, array $data): void
    {
        $this->saveToCache($key, $data);
    }

    public function ClearCacheForConfigForm(): void
    {
        $itemsInMappingForm = [
            CacheEnum::LEAD,
            CacheEnum::CONTACT,
            CacheEnum::ACCOUNT,
            CacheEnum::USER, //Used in form for "Owner for contact"
        ];

        foreach ($itemsInMappingForm as $item) {
            $key = $this->getCacheName($item);
            $this->cacheStorageHelper->delete($key);
        }
    }

    /**
     * @return mixed
     *
     * @throws CachedItemNotFoundException
     */
    private function getFromCache(string $key)
    {
        $key = $this->getCacheName($key);

        if (!$this->cacheStorageHelper->has($key)) {
            throw new CachedItemNotFoundException("Cache item '$key' was not found");
        }

        return $this->cacheStorageHelper->get($key);
    }

    /**
     * @param mixed $data
     */
    private function saveToCache(string $key, $data): void
    {
        $key = $this->getCacheName($key);
        $this->cacheStorageHelper->set($key, $data);
    }

    private function getCacheName($key): string
    {
        return CacheEnum::CACHE_NAMESPACE.'_'.$key;
    }
}
