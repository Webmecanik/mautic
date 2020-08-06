<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticOwnerManagerBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\LeadBundle\Entity\TimelineTrait;

class OwnerManagerLogRepository extends CommonRepository
{
    use TimelineTrait;

    /**
     * Updates lead ID (e.g. after a lead merge).
     *
     * @param $fromLeadId
     * @param $toLeadId
     */
    public function updateLead($fromLeadId, $toLeadId)
    {
        // First check to ensure the $toLead doesn't already exist
        $results = $this->_em->getConnection()->createQueryBuilder()
            ->select('pl.owner_manager_id')
            ->from(MAUTIC_TABLE_PREFIX.'owner_manager_log', 'pl')
            ->where('pl.lead_id = '.$toLeadId)
            ->execute()
            ->fetchAll();
        $actions = [];
        foreach ($results as $r) {
            $actions[] = $r['owner_manager_id'];
        }

        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX.'owner_manager_log')
            ->set('lead_id', (int) $toLeadId)
            ->where('lead_id = '.(int) $fromLeadId);

        if (!empty($actions)) {
            $q->andWhere(
                $q->expr()->notIn('owner_manager_id', $actions)
            )->execute();

            // Delete remaining leads as the new lead already belongs
            $this->_em->getConnection()->createQueryBuilder()
                ->delete(MAUTIC_TABLE_PREFIX.'owner_manager_log')
                ->where('lead_id = '.(int) $fromLeadId)
                ->execute();
        } else {
            $q->execute();
        }
    }

    /**
     * Get a lead's point log.
     *
     * @param int|null $leadId
     *
     * @return array
     */
    public function getLeadTimelineEvents($leadId = null, array $options = [])
    {
        $query = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->from(MAUTIC_TABLE_PREFIX.'owner_manager_log', 'lp')
            ->select('lp.owner_manager_id as eventName, lp.owner_id as actionName, lp.date_fired as dateFired, lp.owner_manager_id as type,  lp.id, lp.lead_id');

        if ($leadId) {
            $query->where('lp.lead_id = '.(int) $leadId);
        }

        if (isset($options['search']) && $options['search']) {
            $query->andWhere($query->expr()->orX(
                $query->expr()->like('lp.event_name', $query->expr()->literal('%'.$options['search'].'%')),
                $query->expr()->like('lp.action_name', $query->expr()->literal('%'.$options['search'].'%'))
            ));
        }

        return $this->getTimelineResults($query, $options, 'lp.event_name', 'lp.date_fired', [], ['dateFired']);
    }
}
