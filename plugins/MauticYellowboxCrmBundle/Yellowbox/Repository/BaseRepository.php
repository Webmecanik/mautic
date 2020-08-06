<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository;

use Mautic\IntegrationsBundle\Sync\Logger\DebugLogger;
use MauticPlugin\MauticYellowboxCrmBundle\Exceptions\CachedItemNotFoundException;
use MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidQueryArgumentException;
use MauticPlugin\MauticYellowboxCrmBundle\Integration\Provider\YellowboxSettingProvider;
use MauticPlugin\MauticYellowboxCrmBundle\Integration\YellowboxCrmIntegration;
use MauticPlugin\MauticYellowboxCrmBundle\Sync\ValueNormalizer\YellowboxValueNormalizer;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Connection;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\Account;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\BaseModel;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\Contact;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\Event;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\Lead;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\ModuleFieldInfo;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\ModuleInfo;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Model\User;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository\Cache\FieldCache;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository\Direction\FieldDirectionFactory;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository\Direction\FieldDirectionInterface;
use MauticPlugin\MauticYellowboxCrmBundle\Yellowbox\Repository\Mapping\ModelFactory;
use Psr\Log\LogLevel;

/**
 * Class BaseRepository.
 */
abstract class BaseRepository
{
    /** @var Connection */
    protected $connection;

    /**
     * @var FieldCache
     */
    protected $fieldCache;

    /**
     * @var ModelFactory
     */
    protected $modelFactory;

    /**
     * @var FieldDirectionFactory
     */
    protected $fieldDirectionFactory;

    /**
     * @var array
     */
    private $cache;

    /**
     * @var YellowboxSettingProvider
     */
    private $yellowboxSettingProvider;

    public function __construct(
        Connection $connection,
        FieldCache $fieldCache,
        ModelFactory $modelFactory,
        FieldDirectionFactory $fieldDirectionFactory,
        YellowboxSettingProvider $yellowboxSettingProvider
    ) {
        $this->connection               = $connection;
        $this->fieldCache               = $fieldCache;
        $this->modelFactory             = $modelFactory;
        $this->fieldDirectionFactory    = $fieldDirectionFactory;
        $this->yellowboxSettingProvider = $yellowboxSettingProvider;
    }

    /**
     * @throws \Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidQueryArgumentException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\YellowboxPluginException
     */
    public function describe(): ModuleInfo
    {
        $key = $this->getModuleFromRepositoryName();
        try {
            return $this->fieldCache->getModuleInfo($key);
        } catch (CachedItemNotFoundException $e) {
        }

        $fieldDirection = $this->getFieldDirection();
        $idTable        = $this->getTableIdFromRepositoryName();

        $fields = $this->connection->get('field/query', ['idTable' => $idTable]);

        if ($this->yellowboxSettingProvider->getAddressSettings()->isEnabled($key)) {
            $fields = array_merge($fields, $this->yellowboxSettingProvider->getAddressSettings()->getFieldsToSync());
        }

        $moduleInfo = new ModuleInfo(
            $fields,
            $fieldDirection
        );
        $this->fieldCache->setModuleInfo($key, $moduleInfo);

        return $moduleInfo;
    }

    /**
     * @param array  $where
     * @param string $columns
     *
     * @throws InvalidQueryArgumentException
     * @throws \Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\YellowboxPluginException
     */
    public function findBy($where = [], $columns = '*'): array
    {
        return $this->findByInternal($where, $columns);
    }

    /**
     * @param array  $where
     * @param string $columns
     *
     * @return mixed|null
     *
     * @throws InvalidQueryArgumentException
     * @throws \Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\YellowboxPluginException
     */
    public function findOneBy($where = [], $columns = '*')
    {
        $findResult = $this->findBy($where, $columns);

        if (!count($findResult)) {
            return null;
        }

        if (count($findResult) > 1) {
            throw new InvalidQueryArgumentException('Invalid query. Query returned more than one result.');
        }

        return array_shift($findResult);
    }

    /**
     * @throws InvalidQueryArgumentException
     * @throws \Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\YellowboxPluginException
     */
    public function update(BaseModel $module): BaseModel
    {
        $payload  = $this->getPayload($module);
        $response = $this->connection->post('record/v2', $payload);

        DebugLogger::log(YellowboxCrmIntegration::NAME, 'Updating '.$this->getModuleFromRepositoryName().' '.$module->getId());

        $this->syncAddress($module);

        return $module;
    }

    /**
     * @throws InvalidQueryArgumentException
     * @throws \Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\YellowboxPluginException
     */
    private function syncAddress(BaseModel $module)
    {
        if (!$this->yellowboxSettingProvider->getAddressSettings()->isEnabled($this->getModuleFromRepositoryName())) {
            return;
        }
        $addressFields = $module->dehydrateAddress();
        if (!empty($addressFields)) {
            $address = $this->getAddress($module->getId());
            // update address
            if ($address) {
                $addressToUpdate = array_merge((array) $address, $addressFields);
                try {
                    $this->connection->post('address', $addressToUpdate);
                    DebugLogger::log(YellowboxCrmIntegration::NAME,
                        sprintf(
                            'Update address %s for object %s and %s with address %s',
                            $address->id,
                            $this->getModuleFromRepositoryName(),
                            $module->getId(),
                            json_encode($addressToUpdate)
                        )
                    );
                } catch (\Exception $exception) {
                    DebugLogger::log(YellowboxCrmIntegration::NAME, 'Failed update address '.$address->id.' for object '.$this->getModuleFromRepositoryName().' and '.$module->getId(), null, [], LogLevel::ERROR);
                }
            } else {
                try {
                    // create address
                    $responseIdAddress = $this->connection->post(
                        'address/create?IdFromContactOrSociety='.$module->getId(),
                        $addressFields
                    );
                    $payloadLinkAddressAndObject = [
                        'idElement'   => $module->getId(),
                        'idAdress'    => $responseIdAddress,
                        'addressType' => $this->yellowboxSettingProvider->getAddressSettings()->getType(),
                        'principal'   => true,
                    ];
                    // link to contact
                    $this->connection->post('address/updatelink', $payloadLinkAddressAndObject);
                    DebugLogger::log(YellowboxCrmIntegration::NAME,
                        sprintf(
                            'Create address %s for object %s and %s with address fields %s',
                            $responseIdAddress,
                            $this->getModuleFromRepositoryName(),
                            $module->getId(),
                            json_encode($addressFields)
                        )
                    );
                } catch (\Exception $exception) {
                    DebugLogger::log(YellowboxCrmIntegration::NAME,
                        'Failed create for object '.$this->getModuleFromRepositoryName(
                        ).' and '.$module->getId()
                    );
                }
            }
        }
    }

    /**
     * @throws InvalidQueryArgumentException
     * @throws \Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\YellowboxPluginException
     */
    public function getReportData(string $operation, array $query = []): array
    {
        $result = $this->connection->post($operation, $query);
        $return = [];
        foreach ($result->content as $moduleObject) {
            $model    = $this->getModel((array) $moduleObject);
            $this->appendAdressData($model);
            $return[] = $model;
        }

        return $return;
    }

    /**
     * @return array|ModuleFieldInfo[]
     *
     * @throws InvalidQueryArgumentException
     * @throws \Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\YellowboxPluginException
     */
    public function getMappableFields(): array
    {
        return $this->getEditableFields();
    }

    /**
     * @return array|ModuleFieldInfo[]
     *
     * @throws InvalidQueryArgumentException
     * @throws \Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\YellowboxPluginException
     */
    public function getRequiredFields(): array
    {
        /** @var ModuleInfo $moduleFields */
        $moduleFields = $this->describe()->getFields();

        $fields = [];
        /** @var ModuleFieldInfo $fieldInfo */
        foreach ($moduleFields as $fieldInfo) {
            if ($fieldInfo->isRequired()) {
                $fields[$fieldInfo->getName()] = $fieldInfo;
            }
        }

        return $fields;
    }

    /**
     * @return array|ModuleFieldInfo[]
     *
     * @throws InvalidQueryArgumentException
     * @throws \Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\YellowboxPluginException
     */
    protected function getEditableFields(): array
    {
        /** @var ModuleInfo $moduleFields */
        $moduleFields = $this->describe()->getFields();

        $fields = [];
        foreach ($moduleFields as $fieldInfo) {
            if ($fieldInfo->isEditable()) {
                $fields[$fieldInfo->getName()] = $fieldInfo;
            }
        }

        return $fields;
    }

    /**
     * @param array  $where
     * @param string $columns
     *
     * @throws InvalidQueryArgumentException
     * @throws \Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\YellowboxPluginException
     */
    protected function findByInternal($where = [], $columns = []): array
    {
        $moduleName = $this->getModuleFromRepositoryName();

        $query  = $moduleName;
        $result = $this->connection->get($query);
        $return = [];
        foreach ($result as $key => $moduleObject) {
            $moduleData = (array) $moduleObject;

            if (!empty($where)) {
                foreach ($where as $key=>$value) {
                    if (isset($moduleData[$key]) && $moduleData[$key] != $value) {
                        continue 2;
                    }
                }
            }

            $return[] = $this->getModel($moduleData);
        }

        return $return;
    }

    /**
     * @return BaseModel|Account|Contact|Lead|Event|User
     *
     * @throws InvalidQueryArgumentException
     * @throws \Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\YellowboxPluginException
     */
    protected function createUnified(BaseModel $module): BaseModel
    {
        $this->findExisting($module);

        $payload  = $this->getPayload($module);
        $response = $this->connection->post('record/v2', $payload);
        $module->set('id', (string) $response);
        $this->syncAddress($module);

        return $module;
    }

    private function findExisting(BaseModel $module)
    {
        $requireField = $this->getRequiredField();
        $results      = $this->getResultsByField($requireField, 'EQUAL', $module->getData()[$requireField]);
        if (!empty($results)) {
            /** @var BaseModel $result */
            $result = reset($results);
            $module->set('ID_ELEMENT', $result->getId());
        }
    }

    public function getResultsByField($fieldName, $operator, $value)
    {
        $field       = $this->getFieldFromRepositoryName($fieldName);
        $reportQuery = '
            {
    "searchValues": {
        "table": {
            "id": "'.$this->getTableFromRepositoryName()->id.'",
            "name": "'.$this->getTableFromRepositoryName()->name.'"
        },
        "values": [
            {
                "field": '.json_encode($field).',
                "value": "'.$value.'",
                "operator": "'.$operator.'"
            }
        ]
    },
    "pageable": {
        "pageNumber": 0,
        "pageSize": 0
    }
}
';

        return $this->getReportData('record/queryvaluespage', json_decode($reportQuery, true));
    }

    /**
     * @return mixed
     *
     * @throws InvalidQueryArgumentException
     * @throws \Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\YellowboxPluginException
     */
    public function getPayload(BaseModel $module)
    {
        $table  = $this->getTableFromRepositoryName();
        $fields = json_decode(json_encode($this->getTableFields($table->id)), true);
        $record = json_decode('
        {
  "record": {
  "id": "'.$module->getId().'",
    "table": {
      "id": "'.$table->id.'",
      "name": "'.$table->name.'"
    }
  },
  "typeImport": 0
}
        ', true);

        $fieldsValues = $module->dehydrate();
        foreach ($fields as $field) {
            if (!isset($fieldsValues[$field['dbName']])) {
                continue;
            }
            $record['record']['values'][] = [
                'field'=> $field,
                'value'=> $fieldsValues[$field['dbName']],
            ];
        }

        return $record;
    }

    /**
     * @throws InvalidQueryArgumentException
     * @throws \Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\YellowboxPluginException
     */
    public function getTableIdFromRepositoryName(): int
    {
        return (int) $this->getTableFromRepositoryName()->id;
    }

    /**
     * @return object
     *
     * @throws InvalidQueryArgumentException
     * @throws \Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\YellowboxPluginException
     */
    public function getTableFromRepositoryName(): \stdClass
    {
        $repoName = $this->getModuleFromRepositoryName();

        if (!isset($this->cache[__METHOD__])) {
            $tables                  = $this->connection->get('table');
            $this->cache[__METHOD__] = $tables;
        }
        $tables = $this->cache[__METHOD__];

        foreach ($tables as $table) {
            // some table names start with 'c'.$table->id
            $normalizeTable = str_replace('c'.$table->id, '', $table->name);
            if ($repoName === $normalizeTable) {
                return $table;
            }
        }
    }

    /**
     * @return stdClass
     *
     * @throws InvalidQueryArgumentException
     * @throws \Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\YellowboxPluginException
     */
    public function getFieldFromRepositoryName(string $fieldName): \stdClass
    {
        $table  = $this->getTableFromRepositoryName();
        $fields = $this->getTableFields($table->id);
        foreach ($fields as $field) {
            // some table names start with 'c'.$table->id
            $normalizeDBName = YellowboxValueNormalizer::getTableNameWithoutPrefix($field->id, $field->dbName);
            if ($fieldName == $normalizeDBName) {
                return $field;
            }
        }

        // modified date
        $fields = $this->getTableFields(3);
        foreach ($fields as $field) {
            // some table names start with 'c'.$table->id
            $normalizeDBName = YellowboxValueNormalizer::getTableNameWithoutPrefix($field->id, $field->dbName);
            if ($fieldName == $normalizeDBName) {
                return $field;
            }
        }
    }

    /**
     * @param $idTable
     *
     * @return mixed
     *
     * @throws InvalidQueryArgumentException
     * @throws \Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\YellowboxPluginException
     */
    private function getTableFields($idTable)
    {
        $key = __METHOD__.$idTable;
        if (!isset($this->cache[$key])) {
            $fields            = $this->connection->get('field/query', ['idTable'=>$idTable]);
            $this->cache[$key] = $fields;
        }

        return $this->cache[$key];
    }

    private function getRequiredField()
    {
        $requiredFields         = $this->getRequiredFields();
        $requiredField          = reset($requiredFields);

        return $requiredField->getName();
    }

    abstract public function getModuleFromRepositoryName(): string;

    /**
     * @return BaseModel|Contact|Account|Lead
     */
    abstract protected function getModel(array $objectData);

    abstract protected function getFieldDirection(): FieldDirectionInterface;

    /**
     * @param $id
     *
     * @return mixed|\Psr\Http\Message\ResponseInterface
     *
     * @throws InvalidQueryArgumentException
     * @throws \Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\YellowboxPluginException
     */
    private function getAddress($id)
    {
        $addressess = $this->connection->get('address/element/'.$id);
        if (is_array($addressess)) {
            foreach ($addressess as $address) {
                if (!empty($address->principal)) {
                    return $address;
                }
            }
        }
    }

    /**
     * @throws InvalidQueryArgumentException
     * @throws \Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\AccessDeniedException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\DatabaseQueryException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\InvalidRequestException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\SessionException
     * @throws \MauticPlugin\MauticYellowboxCrmBundle\Exceptions\YellowboxPluginException
     */
    private function appendAdressData(BaseModel $model)
    {
        if (!$this->yellowboxSettingProvider->getAddressSettings()->isEnabled($this->getModuleFromRepositoryName())) {
            return;
        }

        if ($address = $this->getAddress($model->getId())) {
            $model->append((array) $address);
        }
    }
}
