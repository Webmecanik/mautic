<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticExtendEmailFieldsBundle\EventListener;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\ReportEvents;
use MauticPlugin\MauticExtendEmailFieldsBundle\Helper\ExtendeEmailFieldsIntegrationHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ReportSubscriber implements EventSubscriberInterface
{
    const CONTEXT_EMAILS      = 'emails';

    /**
     * @var Connection
     */
    protected $db;

    /**
     * @var ExtendeEmailFieldsIntegrationHelper
     */
    private $emailFieldsIntegrationHelper;

    /**
     * ReportSubscriber constructor.
     */
    public function __construct(Connection $db, ExtendeEmailFieldsIntegrationHelper $emailFieldsIntegrationHelper)
    {
        $this->db                           = $db;
        $this->emailFieldsIntegrationHelper = $emailFieldsIntegrationHelper;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            ReportEvents::REPORT_ON_BUILD          => ['onReportBuilder', -5],
            ReportEvents::REPORT_ON_GENERATE       => ['onReportGenerate', 5],
        ];
    }

    /**
     * @return array
     */
    private function getCustomColumns()
    {
        $prefix               = 'eef.';

        return [
            $prefix.'extra1' => [
                'label' => $this->emailFieldsIntegrationHelper->getLabel('extra1'),
                'type'  => 'string',
            ],
            $prefix.'extra2' => [
                'label' => $this->emailFieldsIntegrationHelper->getLabel('extra2'),
                'type'  => 'string',
            ],
        ];
    }

    /**
     * Add available tables and columns to the report builder lookup.
     */
    public function onReportBuilder(ReportBuilderEvent $event)
    {
        if (!$this->emailFieldsIntegrationHelper->isActive()) {
            return;
        }

        if (!$event->checkContext(self::CONTEXT_EMAILS)) {
            return;
        }
        $columns = $this->getCustomColumns();
        $data    = [
            'columns'      => $columns,
        ];
        $event->appendToTable(self::CONTEXT_EMAILS, $data);
    }

    /**
     * Initialize the QueryBuilder object to generate reports from.
     */
    public function onReportGenerate(ReportGeneratorEvent $event)
    {
        $context         = $event->getContext();
        $qb              = $event->getQueryBuilder();
        $extendColumns   = ['eef.extra1', 'eef.extra2'];

        switch ($context) {
            case self::CONTEXT_EMAILS:
                if ($event->hasColumn($extendColumns)) {
                    $this->addExtendedEmailFieldsTable($qb);
                }
                break;
        }

        $event->setQueryBuilder($qb);
    }

    /**
     * Add the Do Not Contact table to the query builder.
     */
    private function addExtendedEmailFieldsTable(QueryBuilder $qb)
    {
        $fromAlias = 'e';
        $table     = MAUTIC_TABLE_PREFIX.'email_extend_fields';
        $alias     = 'eef';

        if (!$this->isJoined($qb, $table, $fromAlias, $alias)) {
            $qb->leftJoin(
                $fromAlias,
                $table,
                $alias,
                'e.id = eef.email_id'
            );
        }
    }

    private function isJoined($query, $table, $fromAlias, $alias)
    {
        $joins = $query->getQueryParts()['join'];
        if (empty($joins) || (!empty($joins) && empty($joins[$fromAlias]))) {
            return false;
        }

        foreach ($joins[$fromAlias] as $join) {
            if ($join['joinTable'] == $table && $join['joinAlias'] == $alias) {
                return true;
            }
        }

        return false;
    }
}
