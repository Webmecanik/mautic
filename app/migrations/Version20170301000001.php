<?php
/**
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @see        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

/**
 * Migration.
 */
class Version20170301000001 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE {$this->prefix}campaign_lead_event_log CHANGE rotation rotation INT NOT NULL;");

        $this->addSql("DROP INDEX campaign_leads ON {$this->prefix}campaign_lead_event_log;");

        $this->addSql("ALTER TABLE {$this->prefix}campaign_leads CHANGE rotation rotation INT NOT NULL;");
    }
}
