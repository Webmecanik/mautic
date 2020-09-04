<?php

declare(strict_types=1);

/*
 * @copyright   <year> Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        https://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20200904083422 extends AbstractMauticMigration
{
    /**
     * Notification about upgrade for customer.
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function postUp(Schema $schema): void
    {
        $configParameterBag          = (new \Mautic\CoreBundle\Loader\ParameterLoader())->getParameterBag();
        if (false !== strpos($configParameterBag->get('portal_url'), 'patronpoint')) {
            throw new SkipMigration('Migrations skipped for PP');

            return;
        }

        $configLocale = $configParameterBag->get('locale');

        // Check if there are even boolean fields to worry about
        $qb = $this->connection->createQueryBuilder();
        $qb->select('u.id, u.locale')
            ->from($this->prefix.'users', 'u');
        $users = $qb->execute()->fetchAll();
        if (count($users)) {
            foreach ($users as $key => $user) {
                $userLocale = !empty($user['locale']) ? $user['locale'] : $configLocale;

                if ('fr' == $userLocale) {
                    $header  = 'Votre compte Webmecanik Automation vient être mis à jour en 3.0.11';
                    $message = 'Consultez <a href="https://www.webmecanik.com/changelog/" target="_blank"><u>la note de mise à jour ici</u></a>.';
                } else {
                    $header  = 'Your instance has been updated to Webmecanik Automation 3.0.11';
                    $message = 'Check <a href="https://en.webmecanik.com/changelog/" target="_blank"><u>the release note here</u></a>.';
                }

                $sql = "INSERT INTO `{$this->prefix}notifications` (`user_id`, `type`, `header`, `message`, `date_added`, `icon_class`, `is_read`) VALUES (".$user['id'].", 'info', '".$header."', '".$message."', NOW(), 'fa-trophy', 0);
        ";

                $stmt = $this->connection->prepare($sql);
                $stmt->execute();
            }
        }
    }
}
