<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper\Chart;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Helper\ColorHelper;

abstract class AbstractChart
{
    /**
     * Datasets of the chart.
     *
     * @var array
     */
    protected $datasets = [];

    /**
     * Labels of the time axe.
     *
     * @var array
     */
    protected $labels = [];

    /**
     * Date from.
     *
     * @var \DateTime
     */
    protected $dateFrom;

    /**
     * Date to.
     *
     * @var \DateTime
     */
    protected $dateTo;

    /**
     * Timezone data is requested to be in.
     *
     * @var \DateTimeZone
     */
    protected $timezone;

    /**
     * Time unit.
     *
     * @var string
     */
    protected $unit;

    /**
     * True if unit is H, i, or s.
     *
     * @var bool
     */
    protected $isTimeUnit = false;

    /**
     * amount of items.
     *
     * @var int
     */
    protected $amount;

    /**
     * Default Mautic colors.
     *
     * @var array
     */
    public $colors = ['#5D0E4E', '#76AFAD', '#E10F54', '#6A5F7E', '#3B5857', '#F087AA', '#9F0F51', '#2F0727'];

    /**
     * Create a DateInterval time unit.
     *
     * @param string $unit
     *
     * @return \DateInterval
     */
    public function getUnitInterval($unit = null)
    {
        if (!$unit) {
            $unit = $this->unit;
        }

        $isTime = in_array($unit, ['H', 'i', 's']) ? 'T' : '';

        if ('i' == $unit) {
            $unit = 'M';
        }

        return new \DateInterval('P'.$isTime.'1'.strtoupper($unit));
    }

    /**
     * Helper function to shorten/truncate a string.
     *
     * @param string $string
     * @param int    $length
     * @param string $append
     *
     * @return string
     */
    public static function truncate($string, $length = 100, $append = '...')
    {
        $string = trim($string);

        if (strlen($string) > $length) {
            $string = wordwrap($string, $length);
            $string = explode("\n", $string, 2);
            $string = $string[0].$append;
        }

        return $string;
    }

    /**
     * Sets the clones of the date range and validates it.
     */
    public function setDateRange(\DateTime $dateFrom, \DateTime $dateTo)
    {
        $this->dateFrom = clone $dateFrom;
        $this->dateTo   = clone $dateTo;

        // a diff of two identical dates returns 0, but we expect 24 hours
        if ($dateFrom == $dateTo) {
            $this->dateTo->modify('+1 day');
        }

        // If today, adjust dateTo to be end of today if unit is not time based or to the current hour if it is
        $now = new \DateTime();
        if ($now->format('Y-m-d') == $this->dateTo->format('Y-m-d') && !$this->isTimeUnit) {
            $this->dateTo = $now;
        } elseif (!$this->isTimeUnit) {
            $this->dateTo->setTime(23, 59, 59);
        }

        $this->timezone = $dateFrom->getTimezone();
    }

    /**
     * Modify the date to add one current time unit to it and subtract 1 second.
     * Can be used to get the current day results.
     */
    public function addOneUnitMinusOneSec(\DateTime &$date)
    {
        $date->add($this->getUnitInterval())->modify('-1 sec');
    }

    /**
     * Count amount of time slots of a time unit from a date range.
     *
     * @return int
     */
    public function countAmountFromDateRange()
    {
        switch ($this->unit) {
            case 's':
                $amount = ($this->dateTo->diff($this->dateFrom)->format('%s'));
                ++$amount;
                break;
            case 'i':
                $amount = ($this->dateTo->diff($this->dateFrom)->format('%i'));
                ++$amount;
                break;
            case 'd':
                $amount = ($this->dateTo->diff($this->dateFrom)->format('%a') + 1);
                break;
            case 'W':
                $dayAmount = $this->dateTo->diff($this->dateFrom)->format('%a');
                $amount    = (ceil($dayAmount / 7) + 1);
                break;
            case 'm':
                $amount = $this->dateTo->diff($this->dateFrom)->format('%y') * 12 + $this->dateTo->diff($this->dateFrom)->format('%m');

                // Add 1 month if there are some days left
                if ($this->dateTo->diff($this->dateFrom)->format('%d') > 0) {
                    ++$amount;
                }

                // Add 1 month if count of days are greater or equal than in date to
                if ($this->dateFrom->format('d') >= $this->dateTo->format('d')) {
                    ++$amount;
                }
                break;
            case 'H':
                $dateDiff = $this->dateTo->diff($this->dateFrom);
                $amount   = $dateDiff->h + $dateDiff->days * 24;
                ++$amount;
                break;
            default:
                $amount = ($this->dateTo->diff($this->dateFrom)->format('%'.$this->unit) + 1);
                break;
        }

        return $amount;
    }

    /**
     * Returns appropriate time unit from a date range so the line/bar charts won't be too full/empty.
     *
     * @param $dateFrom
     * @param $dateTo
     *
     * @return string
     */
    public function getTimeUnitFromDateRange($dateFrom, $dateTo)
    {
        $dayDiff = $dateTo->diff($dateFrom)->format('%a');
        $unit    = 'd';

        if ($dayDiff <= 1) {
            $unit = 'H';

            $sameDay    = $dateTo->format('d') == $dateFrom->format('d') ? 1 : 0;
            $hourDiff   = $dateTo->diff($dateFrom)->format('%h');
            $minuteDiff = $dateTo->diff($dateFrom)->format('%i');
            if ($sameDay && !intval($hourDiff) && intval($minuteDiff)) {
                $unit = 'i';
            }
            $secondDiff = $dateTo->diff($dateFrom)->format('%s');
            if (!intval($minuteDiff) && intval($secondDiff)) {
                $unit = 'i';
            }
        }
        if ($dayDiff > 31) {
            $unit = 'W';
        }
        if ($dayDiff > 100) {
            $unit = 'm';
        }
        if ($dayDiff > 1000) {
            $unit = 'Y';
        }

        return $unit;
    }

    /**
     * Generate unique color for the dataset.
     *
     * @param int $datasetId
     *
     * @return ColorHelper
     */
    public function configureColorHelper($datasetId)
    {
        $colorHelper = new ColorHelper();

        if (isset($this->colors[$datasetId])) {
            $color = $colorHelper->setHex($this->colors[$datasetId]);
        } else {
            $color = $colorHelper->buildRandomColor();
        }

        return $color;
    }

    /**
     * @param int|null $campaignId
     * @param string   $fromAlias
     */
    public function addCampaignFilter(QueryBuilder $q, $campaignId = null, $fromAlias = 't')
    {
        if ($campaignId) {
            $q->innerJoin($fromAlias, '(SELECT DISTINCT event_id, lead_id FROM '.MAUTIC_TABLE_PREFIX.'campaign_lead_event_log WHERE campaign_id = :campaignId)', 'clel', $fromAlias.'.source_id = clel.event_id AND '.$fromAlias.'.source = "campaign.event" AND '.$fromAlias.'.lead_id = clel.lead_id')
                ->setParameter('campaignId', $campaignId);
        }
    }

    /**
     * @param int|null $companyId
     * @param string   $fromAlias
     */
    public function addCompanyFilter(QueryBuilder $q, $companyId = null, $fromAlias = 't')
    {
        if (!$companyId) {
            return;
        }

        $sb = $this->connection->createQueryBuilder();

        $sb->select('null')
            ->from(MAUTIC_TABLE_PREFIX.'companies_leads', 'cl')
            ->where(
                $sb->expr()->andX(
                    $sb->expr()->eq('cl.company_id', ':companyId'),
                    $sb->expr()->eq('cl.lead_id', $fromAlias.'.lead_id')
                )
            );

        $q->andWhere(
            sprintf('EXISTS (%s)', $sb->getSql())
        )->setParameter('companyId', $companyId);
    }

    /**
     * @param int|null $segmentId
     * @param string   $fromAlias
     */
    public function addSegmentFilter(QueryBuilder $q, $segmentId = null, $fromAlias = 't')
    {
        if ($segmentId) {
            $sb = $this->connection->createQueryBuilder();

            $sb->select('null')
                ->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'lll')
                ->where(
                    $sb->expr()->andX(
                        $sb->expr()->eq('lll.leadlist_id', ':segmentId'),
                        $sb->expr()->eq('lll.lead_id', $fromAlias.'.lead_id'),
                        $sb->expr()->eq('lll.manually_removed', 0)
                    )
                );

            $q->andWhere(
                sprintf('EXISTS (%s)', $sb->getSql())
            )->setParameter('segmentId', $segmentId);
        }
    }
}
