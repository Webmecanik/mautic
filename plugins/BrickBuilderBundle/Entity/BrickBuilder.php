<?php

declare(strict_types=1);

namespace MauticPlugin\BrickBuilderBundle\Entity;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\EmailBundle\Entity\Email;

class BrickBuilder
{
    const BRICK_BUILDER_ENABLE = 'brick_builder_enable';
    const BOTH_BUILDER_SUPPORT = 'both_builder_support';
    /**
     * @var int
     */
    protected $id;

    /**
     * @var Email
     */
    protected $email;

    /**
     * @var string
     */
    private $customMjml;

    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('bundle_brickbuilder')
            ->setCustomRepositoryClass(BrickBuilderRepository::class)
            ->addNamedField('customMjml', Types::TEXT, 'custom_mjml', true)
            ->addId();

        $builder->createManyToOne(
            'email',
            'Mautic\EmailBundle\Entity\Email'
        )->addJoinColumn('email_id', 'id', true, false, 'CASCADE')->build();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Email
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return BrickBuilder
     */
    public function setEmail(Email $email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getCustomMjml()
    {
        return $this->customMjml;
    }

    /**
     * @param string $customMjml
     *
     * @return BrickBuilder
     */
    public function setCustomMjml($customMjml)
    {
        $this->customMjml = $customMjml;

        return $this;
    }
}
