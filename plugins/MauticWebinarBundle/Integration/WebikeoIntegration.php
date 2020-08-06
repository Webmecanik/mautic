<?php
/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      WebMecanik
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticWebinarBundle\Integration;

use Mautic\LeadBundle\Entity\CompanyLead;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Integration\IntegrationObject;

/**
 * Class ConnectwiseIntegration.
 */
class WebikeoIntegration extends WebinarAbstractIntegration
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'Webikeo';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'oauth2';
    }

    /**
     * Get the array key for the auth token.
     *
     * @return string
     */
    public function getAuthTokenKey()
    {
        return 'token';
    }

    /**
     * Get the keys for the refresh token and expiry.
     *
     * @return array
     */
    public function getRefreshTokenKeys()
    {
        return ['refresh_token'];
    }

    /**
     * {@inheritdoc}
     *
     * @param bool $inAuthorization
     */
    public function getBearerToken($inAuthorization = false)
    {
        if (!$inAuthorization && isset($this->keys[$this->getAuthTokenKey()])) {
            return $this->keys[$this->getAuthTokenKey()];
        }

        return false;
    }

    /**
     * @return string
     */
    public function getApiUrl()
    {
        return 'https://api.webikeo.com/v1';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAccessTokenUrl()
    {
        return $this->getApiUrl().'/login_check';
    }

    /**
     * @return bool
     */
    public function authCallback($settings = [], $parameters = [])
    {
        if ($this->isAuthorized() && isset($settings['use_refresh_token']) && !$settings['use_refresh_token']) {
            return true;
        }
        $autUrl                        = $this->getAccessTokenUrl();
        $settings['encode_parameters'] = 'json';
        $parameters['username']        = $this->keys['username'];
        $parameters['password']        = $this->keys['password'];
        $error                         = false;
        try {
            $response = $this->makeRequest($autUrl, $parameters, 'POST', $settings);
            if (!isset($response[$this->getAuthTokenKey()])) {
                $error = $response;
            } else {
                $this->extractAuthKeys($response, $this->getAuthTokenKey());
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return $error;
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableLeadFields($settings = [])
    {
        return [
            'email'              => ['label' => 'Email', 'type' => 'string', 'required' => true],
            'firstName'          => ['label' => 'First Name', 'type' => 'string', 'required' => true],
            'lastName'           => ['label' => 'Last Name', 'type' => 'string', 'required' => true],
            'phone'              => ['label' => 'Phone', 'type' => 'string'],
            'functionLabel'      => ['label' => 'Position', 'type' => 'string'],
            'companyLabel'       => ['label' => 'Company', 'type' => 'string', 'required' => true],
            'zipCode'            => ['label' => 'Zip Code', 'type' => 'string', 'required' => false],
        ];
    }

    /**
     * @param array $filters
     * @param bool  $forSegment
     *
     * @return array
     */
    public function getWebinars($filters = [], $forSegment = false)
    {
        // les 3 lignes sont commentés pour voir les webinars passés
//        if (empty($filters)) {
//            $filters = ['fromDate' => date('Y-m-d H:i:s')];
//        }

        $filters['page']    = $filters['page'] ?? 0;
        $filters['perPage'] = $filters['perPage'] ?? 100;
        $formattedWebinars  = [];
        do {
            ++$filters['page'];
            $webikeoResponse          = $this->getApiHelper()->getWebinars($filters);
            $webinars                 = $webikeoResponse['_embedded']['webinar'];
            foreach ($webinars as $webinar) {
                if (isset($webinar['id']) and !$forSegment) {
                    $formattedWebinars[$webinar['id']] = $webinar['title'];
                } elseif (isset($webinar['id']) and $forSegment) {
                    $formattedWebinars[] = [
                      'value' => $webinar['id'],
                      'label' => $webinar['title'],
                    ];
                }
            }
        } while ($filters['page'] < $webikeoResponse['page_count']);

        return $formattedWebinars;
    }

    /**
     * @param $webinar
     *
     * @return bool
     */
    public function hasAttendedWebinar($webinar, Lead $lead)
    {
        $filters = ['isNoShow' => true];

        return $this->getWebinarSubscription($webinar, $lead, $filters) ? true : false;
    }

    /**
     * @param $webinar
     * @param array $filters
     *
     * @return mixed
     */
    public function getWebinarSubscription($webinar, Lead $lead, $filters = [])
    {
        $leadEmail = $lead->getEmail();
        if ($leadEmail && isset($webinar['webinar'])) {
            try {
                $subscriptions = $this->getApiHelper()->getSubscriptions($webinar['webinar'], $filters);
            } catch (\Exception $e) {
                return $e->getMessage();
            }

            return $this->findSubscriptionByEmail($subscriptions, $leadEmail);
        }

        return null;
    }

    /**
     * @param $subscriptions
     * @param $email
     *
     * @return mixed
     */
    private function findSubscriptionByEmail($subscriptions, $email)
    {
        if (!isset($subscriptions['_embedded']['subscription'])) {
            return null;
        } else {
            $subscriptions = $subscriptions['_embedded']['subscription'];
        }
        foreach ($subscriptions as $subscription) {
            if ($subscription['user']['email'] == $email) {
                return $subscription;
            }
        }

        return null;
    }

    /**
     * @param $webinar
     * @param $contact
     *
     * @return bool
     */
    public function subscribeToWebinar($webinar, Lead $contact, $campaign)
    {
        if (!isset($webinar['webinar'])) {
            return false;
        }

        $contactDataToPost = $this->formatContactData($contact, $campaign);
        try {
            $response = $this->getApiHelper()->subscribeContact($webinar['webinar'], $contactDataToPost);
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return isset($response['source']);
    }

    /**
     * @param $campaign
     *
     * @return array
     */
    private function formatContactData(Lead $contact, $campaign)
    {
        $matchedData = $this->populateLeadData($contact);

        return [
            'user'             => $matchedData,
            'trackingCampaign' => $campaign,
        ];
    }

    public function getWebinarAllSubscribers($webinar, $isNoShow = null, $page)
    {
        $filters = [];
        if (null !== $isNoShow) {
            $filters['isNoShow'] = $isNoShow;
        }
        $filters['page']    = $page;
        $filters['perPage'] = 100;

        return $this->getApiHelper()->getSubscriptions($webinar, $filters);
    }

    public function getSubscribersForSegmentProcessing($webinar, $isNoShow = null, $segmentId, $page = 1)
    {
        $webinarSubscriberObject = new IntegrationObject('WebinarSubscriber', 'lead');
        $subscriberObject        = new IntegrationObject('Contact', 'lead');
        $webikeoResponse         = $this->getWebinarAllSubscribers($webinar, $isNoShow, $page);
        $subscribers             = isset($webikeoResponse['_embedded']['subscription']) ? $webikeoResponse['_embedded']['subscription'] : [];
        $paginationInfo          = isset($webikeoResponse['_links']) ? $webikeoResponse['_links'] : [];
        if (empty($subscribers)) {
            return;
        }

        // 2 chaines de debug, a decommanter pour faire entrer un seul lead dans la boucle.
//        $subscribers = [unserialize('a:21:{s:6:"source";s:4:"site";s:20:"personnalSessionLink";s:69:"https://webinar.webikeo.com/join/YTRmOGQxMmRjYzVmNzFlZSwxODIyMSwzNDQy";s:9:"createdAt";s:24:"2018-03-11T01:46:22+0100";s:9:"updatedAt";s:24:"2018-04-22T17:58:54+0200";s:9:"engagedAt";s:24:"2018-04-22T17:05:50+0200";s:9:"hasViewed";b:0;s:12:"hasContacted";b:0;s:13:"hasDownloaded";b:1;s:8:"hasRated";b:0;s:11:"hasReplayed";b:1;s:6:"rating";N;s:13:"ratingComment";N;s:7:"logHash";N;s:11:"minutesLive";i:0;s:13:"minutesReplay";i:53;s:2:"id";i:1032124;s:4:"user";a:13:{s:8:"language";s:2:"fr";s:10:"domainName";s:21:"commerce-distribution";s:5:"email";N;s:9:"firstName";s:5:"regis";s:8:"lastName";s:6:"lenoir";s:12:"companyLabel";s:12:"Expatrimonia";s:11:"companySize";a:2:{s:5:"label";s:27:"1 seul employé (moi-même)";s:2:"id";i:1;}s:13:"functionLabel";s:3:"ceo";s:14:"departmentName";s:18:"direction-generale";s:12:"countryLabel";s:6:"France";s:5:"phone";s:13:"09 75 183 198";s:2:"id";i:5090;s:7:"country";a:3:{s:2:"id";i:73;s:5:"label";s:6:"France";s:4:"code";s:2:"FR";}}s:28:"subscriptionLiveCtaTrackings";a:0:{}s:28:"subscriptionFormFieldAnswers";a:3:{i:0;a:5:{s:2:"id";i:13770;s:5:"value";s:10:"Entreprise";s:9:"createdAt";s:24:"2018-03-11T01:46:22+0100";s:15:"formFieldOption";a:2:{s:2:"id";i:519;s:5:"label";s:10:"Entreprise";}s:9:"formField";a:2:{s:2:"id";i:160;s:5:"label";s:15:"Vous êtes une:";}}i:1;a:5:{s:2:"id";i:13771;s:5:"value";s:3:"Oui";s:9:"createdAt";s:24:"2018-03-11T01:46:22+0100";s:15:"formFieldOption";a:2:{s:2:"id";i:520;s:5:"label";s:3:"Oui";}s:9:"formField";a:2:{s:2:"id";i:161;s:5:"label";s:44:"Avez-vous un projet de marketing automation?";}}i:2;a:5:{s:2:"id";i:13772;s:5:"value";s:43:"activite de vente immobiliere pour expatrie";s:9:"createdAt";s:24:"2018-03-11T01:46:22+0100";s:15:"formFieldOption";N;s:9:"formField";a:2:{s:2:"id";i:162;s:5:"label";s:27:"Parlez-nous de votre projet";}}}s:7:"liveUrl";s:109:"https://webikeo.fr/webinar/comment-valoriser-les-99-de-visiteurs-qui-ne-convertissent-pas-sur-votre-site/live";s:9:"replayUrl";s:111:"https://webikeo.fr/webinar/comment-valoriser-les-99-de-visiteurs-qui-ne-convertissent-pas-sur-votre-site/replay";}')];
//        $subscribers = [unserialize('a:21:{s:6:"source";s:4:"site";s:20:"personnalSessionLink";s:69:"https://webinar.webikeo.com/join/YTRmOGQxMmRjYzVmNzFlZSwxODIyMSwzNDQy";s:9:"createdAt";s:24:"2018-03-11T01:46:22+0100";s:9:"updatedAt";s:24:"2018-04-22T17:58:54+0200";s:9:"engagedAt";s:24:"2018-04-22T17:05:50+0200";s:9:"hasViewed";b:0;s:12:"hasContacted";b:0;s:13:"hasDownloaded";b:1;s:8:"hasRated";b:0;s:11:"hasReplayed";b:1;s:6:"rating";N;s:13:"ratingComment";N;s:7:"logHash";N;s:11:"minutesLive";i:0;s:13:"minutesReplay";i:53;s:2:"id";i:1032124;s:4:"user";a:13:{s:8:"language";s:2:"fr";s:10:"domainName";s:21:"commerce-distribution";s:5:"email";s:24:"rlenoir@expatrimonia.com";s:9:"firstName";s:5:"regis";s:8:"lastName";s:6:"lenoir";s:12:"companyLabel";s:12:"Expatrimonia";s:11:"companySize";a:2:{s:5:"label";s:27:"1 seul employé (moi-même)";s:2:"id";i:1;}s:13:"functionLabel";s:3:"ceo";s:14:"departmentName";s:18:"direction-generale";s:12:"countryLabel";s:6:"France";s:5:"phone";s:13:"09 75 183 198";s:2:"id";i:5090;s:7:"country";a:3:{s:2:"id";i:73;s:5:"label";s:6:"France";s:4:"code";s:2:"FR";}}s:28:"subscriptionLiveCtaTrackings";a:0:{}s:28:"subscriptionFormFieldAnswers";a:3:{i:0;a:5:{s:2:"id";i:13770;s:5:"value";s:10:"Entreprise";s:9:"createdAt";s:24:"2018-03-11T01:46:22+0100";s:15:"formFieldOption";a:2:{s:2:"id";i:519;s:5:"label";s:10:"Entreprise";}s:9:"formField";a:2:{s:2:"id";i:160;s:5:"label";s:15:"Vous êtes une:";}}i:1;a:5:{s:2:"id";i:13771;s:5:"value";s:3:"Oui";s:9:"createdAt";s:24:"2018-03-11T01:46:22+0100";s:15:"formFieldOption";a:2:{s:2:"id";i:520;s:5:"label";s:3:"Oui";}s:9:"formField";a:2:{s:2:"id";i:161;s:5:"label";s:44:"Avez-vous un projet de marketing automation?";}}i:2;a:5:{s:2:"id";i:13772;s:5:"value";s:43:"activite de vente immobiliere pour expatrie";s:9:"createdAt";s:24:"2018-03-11T01:46:22+0100";s:15:"formFieldOption";N;s:9:"formField";a:2:{s:2:"id";i:162;s:5:"label";s:27:"Parlez-nous de votre projet";}}}s:7:"liveUrl";s:109:"https://webikeo.fr/webinar/comment-valoriser-les-99-de-visiteurs-qui-ne-convertissent-pas-sur-votre-site/live";s:9:"replayUrl";s:111:"https://webikeo.fr/webinar/comment-valoriser-les-99-de-visiteurs-qui-ne-convertissent-pas-sur-votre-site/replay";}')];

        // if email is know, that mean lead attended to webinar

        // listes des contacts issus de l'API webikeo
        $recordList           = $this->getRecordListWebikeo($subscribers);

        // contact deja en base mautic
        $syncedContacts       = $this->integrationEntityModel->getSyncedRecords($subscriberObject, $this->getName(), $recordList);

        $intergtationToMauticContactId = [];
        // add internal_entity_id as key on syncedContacts
        foreach ($syncedContacts as $syncedContact) {
            if ('Contact' == $syncedContact['integration_entity']) {
                $intergtationToMauticContactId[$syncedContact['integration_entity_id']]=$syncedContact['internal_entity_id'];
            }
        }

        //  these synced records need to check the id of the segment first
        $existingContactsIds = array_map(
          function ($contact) {
              return ('Contact' == $contact['integration_entity']) ? $contact['integration_entity_id'] : [];
          },
          $syncedContacts
        );

        $contactsToFetch = array_diff_key($recordList, array_flip($existingContactsIds));

        foreach ($subscribers as $subscriber) {
            if (array_key_exists($subscriber['user']['id'], $contactsToFetch)) {
                $this->createMauticContact($subscriber);
            } else {
                $this->updateMauticContact($intergtationToMauticContactId[$subscriber['user']['id']], $subscriber, 1);
            }
        }

        $this->saveSyncedWebinarSubscribers($recordList, $webinarSubscriberObject, $webinar);

        $nextPage = isset($paginationInfo['next']) ? current($paginationInfo['next']) : false;
        $thisPage = isset($paginationInfo['self']) ? current($paginationInfo['self']) : false;

        if ($nextPage && $nextPage != $thisPage) {
            $getNext = parse_url($nextPage);
            parse_str($getNext['query'], $output);
            if ($output['page']) {
                $this->getSubscribersForSegmentProcessing($webinar, $isNoShow, $segmentId, $output['page']);
            }
        }
    }

    /**
     * function that update or create.
     *
     * @param $data
     *
     * @return mixed
     */
    public function createMauticContact($data)
    {
        $executed = 0;

        if (!empty($data)) {
            $webinarObject = new IntegrationObject('Contact', 'lead');

            if (is_array($data)) {
                $id                    = $data['user']['id'];
                $formattedData         = $this->matchUpData($data['user']);

                //check if there is at least one information in lead, on string of $formattedData must be <> ''
                if (isset(array_count_values($formattedData)['']) && sizeof($formattedData) === array_count_values($formattedData)['']) {
                    $formattedData['lastName'] = 'webikeo Id '.$id;
                }

                if (isset($formattedData['companyLabel'])) {
                    $formattedData['company']=$formattedData['companyLabel'];
                }
                if (isset($formattedData['zipCode'])) {
                    $formattedData['zipcode']=$formattedData['zipCode'];
                }

                $entity                = $this->getMauticLead($formattedData, true);
                if ($entity) {
                    $integrationEntities[] = $this->saveSyncedData($entity, $webinarObject, $id);
                    ++$executed;
                }
                $this->em->clear(Lead::class);
                $this->em->clear(CompanyLead::class);
            }
        }
        if ($integrationEntities) {
            $this->em->getRepository('MauticPluginBundle:IntegrationEntity')->saveEntities($integrationEntities);
        }
        $this->em->clear(IntegrationEntity::class);

        return $executed;
    }

    /**
     * Update Mautic lead with "new" data from Webikeo...  webikeo never overwrite existing data.
     *
     * @param $contactId
     * @param $data
     */
    public function updateMauticContact($leadId, $data)
    {
        if (!empty($data) && is_array($data)) {
            $formattedDatas      = $this->matchUpData($data['user']);
            $leadModel           = $this->leadModel;

            $lead = $leadModel->getEntity($leadId);

            if (is_null($lead->getId())) {
                // lead was deleted
                $this->createMauticContact($data);

                return false;
            }

            //check if there is a field to update:
            $needPersit = false;
            foreach ($formattedDatas as $k => $formattedData) {
                $getMethod = 'get'.ucfirst($k);
                if (method_exists($lead, $getMethod)) {
                    if ('' !== $formattedData && $lead->$getMethod() !== $formattedData) {
                        $setMethod = 'set'.ucfirst($k);
                        $lead->$setMethod($formattedData);
                        $needPersit = true;
                    }
                }
            }
            if (true === $needPersit) {
                $leadModel->saveEntity($lead);
            }
        }

        return true;
    }

    /**
     * @param $records
     *
     * @return array
     */
    public function getRecordListWebikeo($records)
    {
        $recordList = [];

        foreach ($records as $record) {
            $recordId = $record['user']['id'];

            $recordList[$recordId] = [
              'id' => $recordId,
            ];

            $recordList[$recordId]['status']  = [];
            $recordList[$recordId]['status'][]='webinar_subscribed';

            if (true === $record['hasViewed'] || true === $record['hasReplayed'] || true === $record['hasDownloaded']) {
                $recordList[$recordId]['status'][]='webinar_attended';
            } else {
                $recordList[$recordId]['status'][]='webinar_not_attended';
            }
        }

        return $recordList;
    }
}
