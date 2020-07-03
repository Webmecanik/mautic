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
class Version20170301000003 extends AbstractMauticMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE {$this->prefix}dynamic_content_lead_data DROP FOREIGN KEY FK_515B221BD9D0CD7;");
        $this->addSql("ALTER TABLE {$this->prefix}dynamic_content_lead_data ADD CONSTRAINT FK_515B221BD9D0CD7 FOREIGN KEY (dynamic_content_id) REFERENCES dynamic_content (id) ON DELETE CASCADE;");
        $this->addSql("ALTER TABLE {$this->prefix}campaign_leads CHANGE rotation rotation INT NOT NULL;");
    }
}
