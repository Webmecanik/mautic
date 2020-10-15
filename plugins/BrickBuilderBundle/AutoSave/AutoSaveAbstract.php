<?php

declare(strict_types=1);

namespace MauticPlugin\BrickBuilderBundle\AutoSave;

use Mautic\CoreBundle\Helper\CacheStorageHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\EmailBundle\Entity\Email;

class AutoSaveAbstract
{
    const KEY         = 'brickbuilderautosave';

    const EXPIRATION  = 1800;

    /**
     * @var CacheStorageHelper
     */
    protected $cacheStorageHelper;

    /**
     * @var UserHelper
     */
    protected $userHelper;

    /**
     * AutoSave constructor.
     */
    public function __construct(CacheStorageHelper $cacheStorageHelper, UserHelper $userHelper)
    {
        $this->cacheStorageHelper = $cacheStorageHelper;
        $this->userHelper         = $userHelper;
    }

    public function delete(Email $email)
    {
        $this->cacheStorageHelper->delete($this->getCacheKey($email));
    }

    public function has(Email $email)
    {
        return $this->cacheStorageHelper->has($this->getCacheKey($email));
    }

    public function set(array $data, Email $email)
    {
        $this->cacheStorageHelper->set(
            $this->getCacheKey($email),
            json_encode($data),
            self::EXPIRATION
        );
    }

    public function get(Email $email)
    {
        return $this->cacheStorageHelper->get($this->getCacheKey($email));
    }

    private function getCacheKey(Email $email)
    {
        $cacheKey   = [];
        $cacheKey[] = self::KEY;
        $cacheKey[] = $this->userHelper->getUser()->getId();
        $cacheKey[] = $this->getType();
        $cacheKey[] = $email->getId();
        $cacheKey[] = $email->getTemplate();

        return implode('-', $cacheKey);
    }

    protected function getType()
    {
    }
}
