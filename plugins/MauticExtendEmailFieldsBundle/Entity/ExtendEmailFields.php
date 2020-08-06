<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticExtendEmailFieldsBundle\Entity;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\EmailBundle\Entity\Email;

class ExtendEmailFields
{
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
    private $extra1;
    /**
     * @var string
     */
    private $extra2;

    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('email_extend_fields')
            ->setCustomRepositoryClass(ExtendEmailFieldsRepository::class)
            ->addNamedField('extra1', Type::STRING, 'extra1', true)
            ->addNamedField('extra2', Type::STRING, 'extra2', true)
            ->addId();

        $builder->createManyToOne(
            'email',
            'Mautic\EmailBundle\Entity\Email'
        )->addJoinColumn('email_id', 'id', true, false, 'CASCADE')->build();
    }

    /**
     * Prepares the metadata for API usage.
     *
     * @param $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
    {
        $metadata->setGroupPrefix('email_extend_fields')
            ->addListProperties(
                [
                    'id',
                    'email',
                    'extra1',
                    'extra2',
                ]
            )
            ->build();
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
     * @return ExtendEmailFields
     */
    public function setEmail(Email $email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return Email
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getExtra1()
    {
        return $this->extra1;
    }

    /**
     * @param string $extra1
     *
     * @return ExtendEmailFields
     */
    public function setExtra1($extra1)
    {
        $this->extra1 = $extra1;

        return $this;
    }

    /**
     * @return string
     */
    public function getExtra2()
    {
        return $this->extra2;
    }

    /**
     * @param string $extra2
     *
     * @return ExtendEmailFields
     */
    public function setExtra2($extra2)
    {
        $this->extra2 = $extra2;

        return $this;
    }
}
