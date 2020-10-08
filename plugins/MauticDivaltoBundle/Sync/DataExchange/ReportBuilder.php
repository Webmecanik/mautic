<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDivaltoBundle\Sync\DataExchange;

use GuzzleHttp\Exception\GuzzleException;
use Mautic\IntegrationsBundle\Entity\ObjectMappingRepository;
use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Exception\InvalidCredentialsException;
use Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\InputOptionsDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\ObjectIdsDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\FieldDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\ObjectDAO as ReportObjectDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\ReportDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\MauticDivaltoBundle\Connection\Client;
use MauticPlugin\MauticDivaltoBundle\Integration\Config;
use MauticPlugin\MauticDivaltoBundle\Integration\DivaltoIntegration;
use MauticPlugin\MauticDivaltoBundle\Sync\Mapping\Field\Field;
use MauticPlugin\MauticDivaltoBundle\Sync\Mapping\Field\FieldRepository;
use MauticPlugin\MauticDivaltoBundle\Sync\Mapping\Manual\MappingManualFactory;

class ReportBuilder
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var FieldRepository
     */
    private $fieldRepository;

    /**
     * @var ValueNormalizer
     */
    private $valueNormalizer;

    /**
     * @var ReportDAO
     */
    private $report;

    /**
     * ReportBuilder constructor.
     */
    public function __construct(Client $client, Config $config, FieldRepository $fieldRepository)
    {
        $this->client          = $client;
        $this->config          = $config;
        $this->fieldRepository = $fieldRepository;

        // Value normalizer transforms value types expected by each side of the sync
        $this->valueNormalizer = new ValueNormalizer();
    }

    /**
     * @throws GuzzleException
     * @throws IntegrationNotFoundException
     * @throws InvalidCredentialsException
     * @throws PluginNotConfiguredException
     */
    public function build(int $page, array $requestedObjects, InputOptionsDAO $options): ReportDAO
    {
        $this->report = new ReportDAO(DivaltoIntegration::NAME);

        if ($page > 1) {
            // Prevent loop
            return $this->report;
        }

        // Set the options this integration supports (see InputOptionsDAO for others)
        $startDateTime = $options->getStartDateTime();
        $endDateTime   = $options->getEndDateTime();

        foreach ($requestedObjects as $requestedObject) {
            $objectName = $requestedObject->getObject();

            // marketinginbound skip sync to Mautic, just send to integration
            if (MappingManualFactory::LEAD_OBJECT === $objectName) {
                continue;
            }
            if (!$this->config->enabledObject($objectName)) {
                continue;
            }

            $itemsToMautic = [];
            // Fetch selected IDs from inputs
            if ($options->getIntegrationObjectIds()) {
                try {
                    $objectIds = $options->getIntegrationObjectIds()->getObjectIdsFor($objectName);
                    foreach ($objectIds as $objectId) {
                        $response = $this->client->find($objectName, $objectId);
                        if ($response && 200 === $response->getStatusCode()) {
                            $itemsToMautic[] = json_decode($response->getBody()->getContents(), true);
                        }
                    }
                } catch (ObjectNotFoundException $objectNotFoundException) {
                    continue;
                }
            } else {
                // Fetch a list based on modified date
                $itemsToMautic = $this->client->get(
                    $objectName,
                    $startDateTime,
                    $endDateTime,
                    $page
                );
            }

            $this->config->log(
                sprintf(
                    'Try fetch %s %s objects to %s',
                    count($itemsToMautic),
                    $objectName,
                    'Mautic'
                )
            );

            // Add the modified items to the report
            $this->addModifiedItems($objectName, $itemsToMautic);
        }

        return $this->report;
    }

    private function addModifiedItems(string $objectName, array $changeList): void
    {
        // Get the the field list to know what the field types are
        $fields       = $this->fieldRepository->getFields($objectName);
        $mappedFields = $this->config->getMappedFields($objectName);

        foreach ($changeList as $item) {
            $objectDAO = new ReportObjectDAO(
                $objectName,
                // Set the ID from the integration
                $item[$objectName.'_ID'],
                // Set the date/time when the full object was last modified or created
                new \DateTime(!empty($item['srvDateUTC']) ? $item['srvDateUTC'] : time())
            );

            foreach ($item as $fieldAlias => $fieldValue) {
                if (!isset($fields[$fieldAlias]) || !isset($mappedFields[$fieldAlias])) {
                    // Field is not recognized or it's not mapped so ignore
                    continue;
                }

                /** @var Field $field */
                $field = $fields[$fieldAlias];

                // The sync is currently from Integration to Mautic so normalize the values for storage in Mautic
                if (is_null($fieldValue)) {
                    $fieldValue = '';
                }

                $normalizedValue = $this->valueNormalizer->normalizeForMautic(
                    $fieldValue,
                    $field->getDataType()
                );

                // If the integration supports field level tracking with timestamps, update FieldDAO::setChangeDateTime as well
                // Note that the field name here is the integration's
                $objectDAO->addField(new FieldDAO($fieldAlias, $normalizedValue));
            }

            if ($this->config->syncContactsCompany() && MappingManualFactory::CONTACT_OBJECT === $objectName) {
                $normalizedValue = $this->valueNormalizer->normalizeForMautic(
                    $item['customer_ID'],
                    'string'
                );
                $objectDAO->addField(new FieldDAO('customer_ID', $normalizedValue));
            }

            $this->config->log(
                sprintf('Sync %s from Mautic to Integration with values %s', $objectName, urldecode(http_build_query($item, '', ', ')))
            );
            // Add the modified/new item to the report
            $this->report->addObject($objectDAO);
        }
    }
}
