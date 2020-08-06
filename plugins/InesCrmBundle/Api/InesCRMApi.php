<?php

namespace MauticPlugin\InesCrmBundle\Api;

use Mautic\PluginBundle\Exception\ApiErrorException;
use MauticPlugin\MauticCrmBundle\Api\CrmApi;
use MauticPlugin\MauticCrmBundle\Integration\CrmAbstractIntegration;

class InesCRMApi extends CrmApi
{
    const LOGIN_WS_PATH = '/wslogin/login.asmx';

    const CONTACT_MANAGER_WS_PATH = '/ws/wsicm.asmx';

    const CUSTOM_FIELD_WS_PATH = '/ws/wscf.asmx';

    const AUTOMATION_SYNC_WS_PATH = '/ws/WSAutomationSync.asmx';

    private $translator;

    private $syncInfo;

    private $cachedAuthHeader;

    private $loginClient;

    private $contactManagerClient;

    private $customFieldClient;

    private $automationSyncClient;

    private $notification;

    private $logger;

    public function __construct(CrmAbstractIntegration $integration)
    {
        parent::__construct($integration);
        $this->translator   = $integration->getTranslator();
        $this->notification = $integration->getNotificationModel();
        $this->logger       = $integration->getLogger();
    }

    public function getSyncInfo()
    {
        if (is_null($this->syncInfo)) {
            $client = $this->getAutomationSyncClient();

            $response = $client->GetSyncInfo();
            self::cleanList($response->GetSyncInfoResult->CompanyCustomFields->CustomFieldToAuto);
            self::cleanList($response->GetSyncInfoResult->ContactCustomFields->CustomFieldToAuto);

            $this->syncInfo = $response->GetSyncInfoResult;
        }

        return $this->syncInfo;
    }

    public function getClientCustomFields($internalRef)
    {
        $client = $this->getCustomFieldClient();

        try {
            $response = $client->GetCompanyCF(['reference' => $internalRef]);
        } catch (\Exception $e) {
            $this->addErrorInes($e, $client);

            return false;
        }

        self::cleanList($response->GetCompanyCFResult->Values->CustomField);
        self::cleanList($response->GetCompanyCFResult->Definitions->CustomFieldDefinition);
        self::cleanList($response->GetCompanyCFResult->Groups->CustomFieldGroup);

        return $response;
    }

    public function getContactCustomFields($internalRef)
    {
        $client = $this->getCustomFieldClient();
        try {
            $response = $client->GetContactCF(['reference' => $internalRef]);
        } catch (\Exception $e) {
            $this->addErrorInes($e, $client);

            return false;
        }
        self::cleanList($response->GetContactCFResult->Values->CustomField);
        self::cleanList($response->GetContactCFResult->Definitions->CustomFieldDefinition);
        self::cleanList($response->GetContactCFResult->Groups->CustomFieldGroup);

        return $response;
    }

    public function getLeadCustomFields($internalRef)
    {
        $client = $this->getCustomFieldClient();
        try {
            $response = $client->GetLeadCF(['reference' => $internalRef]);
        } catch (\Exception $e) {
            $this->addErrorInes($e, $client);

            return false;
        }
        self::cleanList($response->GetLeadCFResult->Values->CustomField);
        self::cleanList($response->GetLeadCFResult->Definitions->CustomFieldDefinition);
        self::cleanList($response->GetLeadCFResult->Groups->CustomFieldGroup);

        return $response;
    }

    public function createClientCustomField($mappedData)
    {
        $client = $this->getCustomFieldClient();

        return $client->InsertCompanyCF($mappedData);
    }

    public function updateClientCustomField($mappedData)
    {
        $client = $this->getCustomFieldClient();

        return $client->UpdateCompanyCF($mappedData);
    }

    public function createContactCustomField($mappedData)
    {
        $client = $this->getCustomFieldClient();

        return $client->InsertContactCF($mappedData);
    }

    public function createLeadCustomField($mappedData)
    {
        $client = $this->getCustomFieldClient();

        return $client->InsertLeadCF($mappedData);
    }

    public function updateContactCustomField($mappedData)
    {
        $client = $this->getCustomFieldClient();

        return $client->UpdateContactCF($mappedData);
    }

    public function getClient($internalRef)
    {
        $client = $this->getContactManagerClient();

        return $client->GetClient(['reference' => $internalRef]);
    }

    public function getContact($internalRef)
    {
        $client = $this->getContactManagerClient();

        return $client->GetContact(['reference' => $internalRef]);
    }

    public function createClientWithContacts($mappedData)
    {
        $client = $this->getAutomationSyncClient();

        return $client->AddClientWithContacts($mappedData);
    }

    public function createClient($mappedData)
    {
        $client = $this->getAutomationSyncClient();

        return $client->AddClientWithContacts($mappedData);
    }

    public function createContact($mappedData)
    {
        $client = $this->getAutomationSyncClient();

        return $client->AddContact($mappedData);
    }

    public function createLead($mappedData)
    {
        $client = $this->getAutomationSyncClient();

        return $client->AddLead(['info' => $mappedData]);
    }

    public function updateClient($inesClient)
    {
        $client = $this->getAutomationSyncClient();
        try {
            $soapReturn = $client->UpdateClient(['client' => $inesClient]);
        } catch (Exception $e) {
            $this->addErrorInes($e, $client);

            return false;
        }

        return $soapReturn;
    }

    public function updateContact($inesContact)
    {
        $client = $this->getAutomationSyncClient();
        try {
            $soapReturn = $client->UpdateContact(['contact' => $inesContact]);
        } catch (Exception $e) {
            $this->addErrorInes($e, $client);

            return false;
        }

        return $soapReturn;
    }

    private function getLoginClient()
    {
        if (is_null($this->loginClient)) {
            $this->loginClient = self::makeClient(self::LOGIN_WS_PATH);
        }

        return $this->loginClient;
    }

    private function getContactManagerClient()
    {
        if (is_null($this->contactManagerClient)) {
            $this->contactManagerClient = self::makeClient(self::CONTACT_MANAGER_WS_PATH);
            $this->includeAuthHeader($this->contactManagerClient);
        }

        return $this->contactManagerClient;
    }

    private function getCustomFieldClient()
    {
        if (is_null($this->customFieldClient)) {
            $this->customFieldClient = self::makeClient(self::CUSTOM_FIELD_WS_PATH);
            $this->includeAuthHeader($this->customFieldClient);
        }

        return $this->customFieldClient;
    }

    private function getAutomationSyncClient()
    {
        if (is_null($this->automationSyncClient)) {
            $this->automationSyncClient = self::makeClient(self::AUTOMATION_SYNC_WS_PATH);
            $this->includeAuthHeader($this->automationSyncClient);
        }

        return $this->automationSyncClient;
    }

    private function makeClient($path)
    {
        $apiUrl = $this->integration->getApiUrl();

        return new \SoapClient($apiUrl.$path.'?wsdl', ['trace' => true]);
    }

    private function includeAuthHeader($client)
    {
        if (is_null($this->cachedAuthHeader)) {
            $sessionId              = $this->getSessionId();
            $this->cachedAuthHeader = new \SoapHeader('http://webservice.ines.fr', 'SessionID', ['ID' => $sessionId]);
        }

        $client->__setSoapHeaders($this->cachedAuthHeader);
    }

    private function getSessionId()
    {
        $keys   = $this->integration->getDecryptedApiKeys();
        $failed = false;

        try {
            $response = $this->getLoginClient()->authenticationWs([
                'request' => $keys,
            ]);

            if ('failed' === $response->authenticationWsResult->codeReturn) {
                $failed = true;
            }
        } catch (\SoapFault $e) {
            $failed = true;
        }

        if ($failed) {
            throw new ApiErrorException($this->translator->trans('mautic.ines_crm.form.invalid_identifiers'));
        }

        return $response->authenticationWsResult->idSession;
    }

    private static function cleanList(&$dirtyList)
    {
        if (!isset($dirtyList)) {
            $dirtyList = [];
        } elseif (!is_array($dirtyList)) {
            $dirtyList = [$dirtyList];
        }
    }

    private function addErrorInes(\Exception $e, $client)
    {
        $this->notification->addNotification(__METHOD__.'requête vers ines en erreur '.$e->getMessage());
        $this->logger->addError(__METHOD__.'requête vers ines en erreur '.$e->getMessage());

        $this->logger->addError('Last_request : '.$client->__getLastRequest());
        $this->logger->addError('Last_request_header : '.$client->__getLastRequestHeaders());

        mail('ama@webmecanik.com,f.boulanger@inescrm.com', 'Requête vers ines en erreur',
            'instance : '.__DIR__.'<br>requête vers INES :'.$client->__getLastRequest().'<br>header de la requête:'.$client->__getLastRequestHeaders().'<br>réponse de l API INES: '.$e->getMessage(), '', '-f infra@webmecanik.com');
    }
}
