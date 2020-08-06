<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticOwnerManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\UserBundle\Entity\User;

class OwnerManagerLog
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var OwnerManager
     **/
    private $ownerManager;

    /**
     * @var \Mautic\LeadBundle\Entity\Lead
     */
    private $lead;

    /**
     * @var \Mautic\CoreBundle\Entity\IpAddress
     */
    private $ipAddress;

    /** @var int|string */
    private $internalId;
    /**
     * @var \DateTime
     **/
    private $dateFired;

    /**
     * @var User
     */
    private $owner = 0;

    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('owner_manager_log')
            ->setCustomRepositoryClass(OwnerManagerLogRepository::class)
            ->addIndex(['internal_id'], 'internal_id');

        $builder->addId();

        $builder->createManyToOne('owner', User::class)
            ->fetchLazy()
            ->addJoinColumn('owner_id', 'id', true, false, 'SET NULL')
            ->build();

        $builder->createManyToOne('ownerManager', OwnerManager::class)
            ->addJoinColumn('owner_manager_id', 'id', true, false, 'CASCADE')
            ->inversedBy('log')
            ->build();

        $builder->addLead(false, 'CASCADE', false);

        $builder->addIpAddress(true);

        $builder->createField('dateFired', 'datetime')
            ->columnName('date_fired')
            ->build();

        $builder->createField('internalId', 'string')
            ->columnName('internal_id')
            ->length(255)
            ->nullable()
            ->build();
    }

    /**
     * @return mixed
     */
    public function getDateFired()
    {
        return $this->dateFired;
    }

    /**
     * @param mixed $dateFired
     */
    public function setDateFired($dateFired)
    {
        $this->dateFired = $dateFired;
    }

    /**
     * @return mixed
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * @param mixed $ipAddress
     */
    public function setIpAddress($ipAddress)
    {
        $this->ipAddress = $ipAddress;
    }

    /**
     * @return mixed
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @param mixed $lead
     */
    public function setLead($lead)
    {
        $this->lead = $lead;
    }

    /**
     * @return int|string
     */
    public function getInternalId()
    {
        return $this->internalId;
    }

    /**
     * @param int|string $internalId
     */
    public function setInternalId($internalId)
    {
        $this->internalId = $internalId;
    }

    /**
     * @return OwnerManager
     */
    public function getOwnerManager()
    {
        return $this->ownerManager;
    }

    /**
     * @param OwnerManager $ownerManager
     */
    public function setOwnerManager($ownerManager)
    {
        $this->ownerManager = $ownerManager;
    }

    public function setId(int $id): OwnerManagerLog
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setOwner(User $owner): OwnerManagerLog
    {
        $this->owner = $owner;

        return $this;
    }

    public function getOwner(): User
    {
        return $this->owner;
    }
}
