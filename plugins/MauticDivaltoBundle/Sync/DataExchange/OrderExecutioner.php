<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDivaltoBundle\Sync\DataExchange;

use DateTimeImmutable;
use DateTimeZone;
use Mautic\CoreBundle\Helper\ArrayHelper;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\FieldDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\OrderDAO;
use Mautic\LeadBundle\Model\DoNotContact;
use MauticPlugin\MauticDivaltoBundle\Connection\Client;
use MauticPlugin\MauticDivaltoBundle\Integration\Config;
use MauticPlugin\MauticDivaltoBundle\Integration\DivaltoIntegration;
use MauticPlugin\MauticDivaltoBundle\Sync\Mapping\Field\FieldRepository;
use MauticPlugin\MauticDivaltoBundle\Sync\Mapping\Manual\MappingManualFactory;

class OrderExecutioner
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var ValueNormalizer
     */
    private $valueNormalizer;

    /**
     * @var OrderDAO
     */
    private $order;

    /**
     * @var FieldRepository
     */
    private $fieldRepository;

    /**
     * @var Config
     */
    private $config;

    /**
     * OrderExecutioner constructor.
     */
    public function __construct(Client $client, FieldRepository $fieldRepository, DoNotContact $doNotContact, Config $config)
    {
        $this->client          = $client;
        $this->valueNormalizer = new ValueNormalizer();
        $this->fieldRepository = $fieldRepository;
        $this->config          = $config;
    }

    public function execute(OrderDAO $orderDAO): void
    {
        $this->order = $orderDAO;

        if ($this->config->enabledObject(MappingManualFactory::CONTACT_OBJECT)) {
            $objects = ArrayHelper::getValue(MappingManualFactory::CONTACT_OBJECT, $orderDAO->getIdentifiedObjects(), []);
            $this->config->log(
                sprintf(
                    'Try update %s %s objects to %s',
                    count($objects),
                    MappingManualFactory::CONTACT_OBJECT,
                    DivaltoIntegration::DISPLAY_NAME
                )
            );
            $this->updateObjects($objects);
        }

        if ($this->config->enabledObject(MappingManualFactory::CONTACT_OBJECT)) {
            $objects = ArrayHelper::getValue(MappingManualFactory::CONTACT_OBJECT, $orderDAO->getUnidentifiedObjects(), []);

            $this->config->log(
                sprintf(
                    'Try insert %s %s objects to %s',
                    count($objects),
                    MappingManualFactory::CONTACT_OBJECT,
                    DivaltoIntegration::DISPLAY_NAME
                )
            );

            $this->insertObjects($orderDAO->getUnidentifiedObjects());
        }

        sleep(1);
    }

    private function findExistingObjects(string $objectName, array $data)
    {
        $payload        = [];
        $requiredFields = $this->fieldRepository->getRequiredFieldsForMapping($objectName);
        foreach (array_keys($requiredFields) as $requiredField) {
            if (!empty($data[$requiredField])) {
                $payload['filterFields'][] = [
                    'filterKey'    => $requiredField.'Key',
                    'fieldName'    => $requiredField,
                    'operator'     => 'equal',
                    'filterValues' => [
                        $data[$requiredField],
                    ],
                ];
            }
        }
        if (!empty($payload)) {
            $existed = $this->client->get($objectName, null, null, 1, $payload);
            $existed = reset($existed);
            if (!empty($existed)) {
                return $existed[$objectName.'_ID'];
            }
        }

        return false;
    }

    /**
     * @param ObjectChangeDAO[] $objects
     */
    private function updateObjects(array $objects): void
    {
        foreach ($objects as $objectChangeDAO) {
            // validate exists in integration
            $response = $this->client->find($objectChangeDAO->getObject(), $objectChangeDAO->getObjectId());
            if ($response && 404 === $response->getStatusCode()) {
                $this->order->deleteObject($objectChangeDAO);

                continue;
            }

            $data = $this->prepareFieldPayload($objectChangeDAO);
            if ($this->client->upsert('PUT', $objectChangeDAO->getObject(), $data)) {
                $this->order->updateLastSyncDate($objectChangeDAO);
            }
        }
    }

    /**
     * @param ObjectChangeDAO[] $objects
     */
    private function insertObjects(array $objects): void
    {
        $contactObjects = ArrayHelper::getValue(MappingManualFactory::CONTACT_OBJECT, $objects, []);
        /**
         * @var
         * @var ObjectChangeDAO $objectChangeDAO
         */
        foreach ($contactObjects as $mauticId => $objectChangeDAO) {
            $data            = $this->prepareFieldPayload($objectChangeDAO);
            $existedObjectId = $this->findExistingObjects($objectChangeDAO->getObject(), $data);

            // If contact exist, update it and map it
            if ($existedObjectId) {
                $data[$objectChangeDAO->getObject().'_ID'] = $existedObjectId;
                if ($response = $this->client->upsert('PUT', $objectChangeDAO->getObject(), $data)) {
                    $this->order->addObjectMapping(
                        $objectChangeDAO,
                        $objectChangeDAO->getObject(),
                        $existedObjectId
                    );
                }
                continue;
            }

            if ($this->config->enabledObject(MappingManualFactory::LEAD_OBJECT) && isset($objects[MappingManualFactory::LEAD_OBJECT][$mauticId])) {
                $objectChangeDAO = $objects[MappingManualFactory::LEAD_OBJECT][$mauticId];
                $data            = $this->prepareFieldPayload($objectChangeDAO);
                $existedObjectId = $this->findExistingObjects($objectChangeDAO->getObject(), $data);
                // If exist as inbound, just map avoid the next insertObjects action
                if ($existedObjectId) {
                    $this->order->addObjectMapping(
                        $objectChangeDAO,
                        $objectChangeDAO->getObject(),
                        $existedObjectId
                    );
                } else {
                    // create inbound and
                    if ($response = $this->client->upsert('POST', $objectChangeDAO->getObject(), $data)) {
                        $this->order->addObjectMapping(
                            $objectChangeDAO,
                            $objectChangeDAO->getObject(),
                            $data[$objectChangeDAO->getObject().'_ID']
                        );
                    }
                }
            }
        }
    }

    private function prepareFieldPayload(ObjectChangeDAO $objectChangeDAO): array
    {
        if ($id = $objectChangeDAO->getObjectId()) {
            // If the object is identified, just updated with the modified data
            $fields = array_merge(
                $objectChangeDAO->getChangedFields(),
                $objectChangeDAO->getRequiredFields()
            );
            $datum[sprintf('%s_ID', $objectChangeDAO->getObject())] = $objectChangeDAO->getObjectId();
        } else {
            // Otherwise, merge required and changed fields to ensure a full profile.
            $fields                                                 = array_merge($objectChangeDAO->getRequiredFields(), $objectChangeDAO->getChangedFields());
            $datum[sprintf('%s_ID', $objectChangeDAO->getObject())] = $objectChangeDAO->getMappedObject().$objectChangeDAO->getMappedObjectId();
        }
        /** @var FieldDAO $field */
        foreach ($fields as $field) {
            // Transform the data format from Mautic to what the integration expects
            if ('bouncedEmailDetected' === $field->getName()) {
                $datum[$field->getName()] = null;
                if ($objectChangeDAO->getObjectId()) {
                    $response = $this->client->find($objectChangeDAO->getObject(), $objectChangeDAO->getObjectId());
                    if ($response && 200 === $response->getStatusCode()) {
                        $data = json_decode($response->getBody()->getContents(), true);
                        if (array_key_exists('bouncedEmailDetected', $data)) {
                            if ($field->getValue()->getNormalizedValue() > 0 && empty($data['bouncedEmailDetected'])) {
                                $datum[$field->getName()] = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
                            }
                        }
                    }
                }
            } else {
                $datum[$field->getName()] = $this->valueNormalizer->normalizeForIntegration(
                    $field->getValue()
                );
            }
        }

        return $datum;
    }
}
