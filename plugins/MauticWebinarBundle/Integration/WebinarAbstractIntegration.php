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

use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\IntegrationEntity;
use Mautic\PluginBundle\Entity\IntegrationEntityRepository;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use Mautic\PluginBundle\Integration\IntegrationObject;

/**
 * Class WebinarAbstractIntegration.
 */
abstract class WebinarAbstractIntegration extends AbstractIntegration
{
    protected $auth;

    /**
     * @return array
     */
    public function getSupportedFeatures()
    {
        return ['push_subscriptions', 'get_subscriptions'];
    }

    public function setIntegrationSettings(Integration $settings)
    {
        //make sure URL does not have ending /
        $keys = $this->getDecryptedApiKeys($settings);
        if (array_key_exists('url', $keys) && '/' === substr($keys['url'], -1)) {
            $keys['url'] = substr($keys['url'], 0, -1);
            $this->encryptAndSetApiKeys($keys, $settings);
        }

        parent::setIntegrationSettings($settings);
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return [
            'username'  => 'mautic.webinar.form.username',
            'password'  => 'mautic.webinar.form.password',
        ];
    }

    /**
     * Get the array key for client secret.
     *
     * @return string
     */
    public function getClientSecretKey()
    {
        return 'password';
    }

    public function getUsername()
    {
        return 'username';
    }

    /**
     * {@inheritdoc}
     */
    public function sortFieldsAlphabetically()
    {
        return false;
    }

    /**
     * Get the API helper.
     *
     * @return mixed
     */
    public function getApiHelper()
    {
        static $helper;
        if (null === $helper) {
            $class  = '\\MauticPlugin\\MauticWebinarBundle\\Api\\'.$this->getName().'Api';
            $helper = new $class($this);
        }

        return $helper;
    }

    /**
     * @return array
     */
    public function getFormSettings()
    {
        return [
            'requires_callback'      => false,
            'requires_authorization' => true,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function isAuthorized()
    {
        $keys = $this->getKeys();

        return isset($keys[$this->getAuthTokenKey()]);
    }

    /**
     * @param array $settings
     *
     * @return array|mixed
     *
     * @throws \Exception
     */
    public function getFormLeadFields($settings = [])
    {
        return ($this->isAuthorized()) ? $this->getAvailableLeadFields($settings) : [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableLeadFields($settings = [])
    {
        return [];
    }

    /**
     * @param $records
     *
     * @return array
     */
    public function getRecordList($records, $index = null)
    {
        $recordList = [];

        foreach ($records as $record) {
            if ($index and isset($record[$index])) {
                $record = $record[$index];
            }
            $recordList[$record] = [
                'id' => $record,
            ];
        }

        return $recordList;
    }

    /**
     * @param $allSubscribers
     * @param $webinarSubscriberObject
     * @param $webinar
     */
    public function saveSyncedWebinarSubscribers($recordList, $webinarSubscriberObject, $webinar)
    {
        if (empty($recordList)) {
            return;
        }
        $persistEntities     = [];
        $contactsInWebinar   = [];
        $contactsUpdated     = [];
        $leadToIntegrationId = [];
        $mauticObject        = new IntegrationObject('Contact', 'lead');
        $contacts            = $this->integrationEntityModel->getSyncedRecords($mauticObject, $this->getName(), $recordList);

        foreach ($contacts as $subscribers) {
            $contactsInWebinar[$subscribers['internal_entity_id']]   = $subscribers['internal_entity_id'];
            $leadToIntegrationId[$subscribers['internal_entity_id']] = $subscribers['integration_entity_id'];
        }

        $integrationEntityRepo      = $this->em->getRepository('MauticPluginBundle:IntegrationEntity');
        $existingWebinarSubscribers = $integrationEntityRepo->getIntegrationEntities(
            $this->getName(),
            $webinarSubscriberObject->getType(),
            $webinarSubscriberObject->getInternalType(),
            $contactsInWebinar,
            null,
            $webinar
        );

        foreach ($existingWebinarSubscribers as $webinarSubscriber) {
            if ($webinarSubscriber->getIntegrationEntityId() != $webinar) {
                continue;
            }

            $webinarSubscriber->setLastSyncDate(new \DateTime());
            $integrationContactId = $leadToIntegrationId[$webinarSubscriber->getInternalEntityId()];

            $webinarSubscriber->setInternal($recordList[$integrationContactId]['status']);
            $persistEntities[] = $webinarSubscriber;
            $contactsUpdated[] =$integrationContactId;
        }

        foreach ($contacts as $newContact) {
            if (in_array($newContact['integration_entity_id'], $contactsUpdated)) {
                //contact alreaty updated
                continue;
            }

            $newContactStatus  = $recordList[$newContact['integration_entity_id']]['status'];
            $persistEntities[] = $this->createIntegrationEntity(
                $webinarSubscriberObject->getType(),
                $webinar,
                $webinarSubscriberObject->getInternalType(),
                $newContact['internal_entity_id'],
                ['status' => $newContactStatus],
                false
            );
        }

        //then save records.
        if ($persistEntities) {
            $this->em->getRepository('MauticPluginBundle:IntegrationEntity')->saveEntities($persistEntities);
            unset($persistEntities);
            $this->em->clear(IntegrationEntity::class);
        }
    }

    /**
     * @param $entity
     * @param $object
     * @param $mauticObjectReference
     * @param $integrationEntityId
     *
     * @return IntegrationEntity|object|null
     */
    public function saveSyncedData($entity, IntegrationObject $webinarObject, $integrationEntityId)
    {
        /** @var IntegrationEntityRepository $integrationEntityRepo */
        $integrationEntityRepo = $this->em->getRepository('MauticPluginBundle:IntegrationEntity');
        $integrationEntities   = $integrationEntityRepo->getIntegrationEntities(
            $this->getName(),
            $webinarObject->getType(),
            $webinarObject->getInternalType(),
            [$entity->getId()]
        );

        if ($integrationEntities) {
            $integrationEntity = reset($integrationEntities);
            $integrationEntity->setLastSyncDate(new \DateTime());
        } else {
            $integrationEntity = new IntegrationEntity();
            $integrationEntity->setDateAdded(new \DateTime());
            $integrationEntity->setIntegration($this->getName());
            $integrationEntity->setIntegrationEntity($webinarObject->getType());
            $integrationEntity->setIntegrationEntityId($integrationEntityId);
            $integrationEntity->setInternalEntity($webinarObject->getInternalType());
            $integrationEntity->setInternalEntityId($entity->getId());
        }

        return $integrationEntity;
    }
}
