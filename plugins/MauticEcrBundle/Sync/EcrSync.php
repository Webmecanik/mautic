<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEcrBundle\Sync;

use Joomla\Http\Http;
use Mautic\CoreBundle\Helper\ArrayHelper;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Model\DoNotContact;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\MauticEcrBundle\Integration\EcrIntegration;
use MauticPlugin\MauticEcrBundle\Integration\EcrSettings;
use MauticPlugin\MauticEcrBundle\Sync\DAO\InputDAO;

class EcrSync
{
    /**
     * @var Http ;
     */
    protected $connector;

    /**
     * @var EcrSettings
     */
    private $ecrSettings;

    /**
     * @var LeadModel
     */
    private $leadModel;

    /**
     * @var DoNotContact
     */
    private $doNotContact;

    /**
     * EcrSync constructor.
     */
    public function __construct(Http $connector, EcrSettings $ecrSettings, LeadModel $leadModel, DoNotContact $doNotContact)
    {
        $this->connector    = $connector;
        $this->ecrSettings  = $ecrSettings;
        $this->leadModel    = $leadModel;
        $this->doNotContact = $doNotContact;
    }

    /**
     * @return int
     */
    public function syncOrders(InputDAO $inputDAO)
    {
        $data = [
            'user' => $this->ecrSettings->getUser(),
            'key'  => $this->ecrSettings->getKey(),
        ];

        /** @var LeadEventLog $lastLeadEventLog */
        $lastLeadEventLog = $this->leadModel->getEventLogRepository()->findOneBy(
            ['bundle' => EcrIntegration::INTEGRATION_NAME, 'object' => 'order'],
            ['id' => 'DESC', 'objectId' => 'DESC']
        );
        if ($lastLeadEventLog) {
            $data['min_id'] = $lastLeadEventLog->getObjectId();
        } else {
            $data['from'] = $inputDAO->getStartDateTime()->format('Y-m-d');
            $data['to']   = $inputDAO->getEndDateTime()->format('Y-m-d');
        }

        $result   = $this->connector->get(EcrSettings::API_URL.'/api/orders?'.http_build_query($data));
        $response = json_decode($result->body, true);
        $orders   = ArrayHelper::getValue('orders', $response, []);
        $leads    = [];
        $logs     = [];
        $orders   = array_reverse($orders);
        foreach ($orders as $order) {
            $fields = $order;
            unset($fields['contact']);
            unset($fields['contact_payer']);
            unset($fields['birthday']);
            $fields = array_merge($fields, $order['contact']);
            $toSync = $this->marchingFields($fields);
            $lead   = $this->leadModel->checkForDuplicateContact($toSync);
            $this->leadModel->saveEntity($lead);

            if ($this->ecrSettings->syncDnc()) {
                if (!empty($fields['has_newsletter'])) {
                    $this->doNotContact->removeDncForContact($lead->getId(), 'email');
                } else {
                    $this->doNotContact->addDncForContact($lead->getId(), 'email', true, EcrIntegration::INTEGRATION_NAME);
                }
            }

            $leads[] = $lead;
            $log     = new LeadEventLog();
            $log->setLead($lead)
                ->setBundle(EcrIntegration::INTEGRATION_NAME)
                ->setAction($lead->isNew() ? 'created' : 'updated')
                ->setObject('order')
                ->setObjectId($order['id'])
                ->setProperties(['toSync' => $toSync]);
            $logs[$order['id']] = $log;
        }
        ksort($logs);
        $this->leadModel->getEventLogRepository()->saveEntities($logs);

        return count($leads);
    }

    /**
     * @return array
     */
    private function marchingFields(array $fields)
    {
        $matchedFields = $this->ecrSettings->getMatchingFields();
        $toSync        = [];
        foreach ($matchedFields as $matchedField) {
            list($integrationField, $mauticField) = explode('=', $matchedField);
            if (isset($fields[$integrationField])) {
                $valueToSync = $fields[$integrationField];
                switch ($integrationField) {
                    case 'affs':
                        if (!empty($fields[$integrationField])) {
                            $valueToSync = implode('|', array_column($valueToSync, 'aff_code'));
                        }
                        break;
                    case 'birthday':
                        if (!empty($valueToSync['timestamp'])) {
                            $date = new \DateTime();
                            $date->setTimestamp($valueToSync['timestamp']);
                            $valueToSync =  $date->format('Y-m-d');
                        }
                        break;
                    case 'has_newsletter':
                            $valueToSync =  (int) $valueToSync;
                        break;
                }

                $toSync[$mauticField] = $valueToSync;
            }
        }

        return $toSync;
    }
}
