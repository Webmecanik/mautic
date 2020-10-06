<?php

/*
 * @package     Mautic
 * @copyright   2020 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20201002103053 extends AbstractMauticMigration
{
    /**
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        $leadsTable = $schema->getTable($this->prefix.'leads');

        $dropIndexedColumns = ['address1', 'address2', 'facebook', 'fax', 'foursquare', 'googleplus', 'instagram', 'position', 'skype'];

        foreach ($dropIndexedColumns as $dropIndexedColumn) {
            if ($leadsTable->hasIndex("{$this->prefix}{$dropIndexedColumn}_search")) {
                $this->addSql("DROP INDEX `{$this->prefix}{$dropIndexedColumn}_search` ON `{$this->prefix}leads`");
            }
        }
    }

    public function up(Schema $schema)
    {
        // Please modify to your needs
    }
}
