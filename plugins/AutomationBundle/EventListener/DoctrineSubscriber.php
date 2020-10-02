<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\AutomationBundle\EventListener;

use Doctrine\DBAL\Types\StringType;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Tools\ToolEvents;
use Mautic\LeadBundle\Model\FieldModel;
use Monolog\Logger;

/**
 * Class DoctrineSubscriber.
 */
class DoctrineSubscriber implements \Doctrine\Common\EventSubscriber
{
    private $dropIndexedColumns = ['address1', 'address2', 'facebook', 'fax', 'foursquare', 'googleplus', 'instagram', 'position', 'skype'];

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            ToolEvents::postGenerateSchema,
        ];
    }

    public function postGenerateSchema(GenerateSchemaEventArgs $args)
    {
        $schema = $args->getSchema();

        try {
            if (!$schema->hasTable(MAUTIC_TABLE_PREFIX.'lead_fields')) {
                return;
            }

            $objects = [
                'lead'    => 'leads',
                'company' => 'companies',
            ];

            foreach ($objects as $object => $tableName) {
                $table = $schema->getTable(MAUTIC_TABLE_PREFIX.$tableName);

                //get a list of fields
                $fields = $args->getEntityManager()->getConnection()->createQueryBuilder()
                    ->select('f.alias, f.is_unique_identifer as is_unique, f.type, f.object')
                    ->from(MAUTIC_TABLE_PREFIX.'lead_fields', 'f')
                    ->where("f.object = '$object'")
                    ->orderBy('f.field_order', 'ASC')
                    ->execute()->fetchAll();

                // Compile which ones are unique identifiers
                // Email will always be included first
                $uniqueFields = ('lead' === $object) ? ['email' => 'email'] : ['companyemail' => 'companyemail'];
                foreach ($fields as $f) {
                    if ($f['is_unique'] && 'email' != $f['alias']) {
                        $uniqueFields[$f['alias']] = $f['alias'];
                    }
                    $columnDef = FieldModel::getSchemaDefinition($f['alias'], $f['type'], !empty($f['is_unique']));

                    if (!$table->hasColumn($f['alias'])) {
                        continue;
                    }

                    if (in_array($f['alias'], $this->dropIndexedColumns)) {
                        $table->dropIndex(MAUTIC_TABLE_PREFIX.$f['alias'].'_search');
                    }
                }
            }
        } catch (\Exception $e) {
        }
    }
}
