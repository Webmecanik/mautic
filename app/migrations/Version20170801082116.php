<?php

/*
 * @package     Mautic
 * @copyright   2017 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Migrations\SkipMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Auto-generated Migration.
 */
class Version20170801082116 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     *
     * @throws SkipMigrationException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function preUp(Schema $schema)
    {
        if ($schema->getTable("{$this->prefix}leads")->hasIndex("{$this->prefix}points_search")) {
            throw new SkipMigrationException('Schema includes this migration');
        }
        $table = $schema->getTable("{$this->prefix}leads");
        if (sizeof($table->getIndexes()) > 63) {
            throw new SkipMigrationException('This table already has 64 indexes');
        }
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("CREATE INDEX {$this->prefix}points_search ON {$this->prefix}leads (points)");
    }
}
