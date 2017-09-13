<?php

namespace MauticPlugin\MauticCrmBundle\Api;

use GuzzleHttp; // FIXME: to remove along with mock requests
use MauticPlugin\MauticCrmBundle\Integration\CrmAbstractIntegration;
use Mautic\PluginBundle\Exception\ApiErrorException;

class InesCRMApi extends CrmApi
{
    const ROOT_URL = 'https://webservices.inescrm.com';

    const LOGIN_WS_PATH = '/wslogin/login.asmx';

    const CONTACT_MANAGER_WS_PATH = '/ws/wsicm.asmx';

    const CUSTOM_FIELD_WS_PATH = '/ws/wscf.asmx';

    const AUTOMATION_SYNC_WS_PATH = '/ws/WSAutomationSync.asmx';

    private $translator;

    // FIXME: to remove along with mock requests
    private $client;

    private $loginClient;

    private $contactManagerClient;

    private $customFieldClient;

    private $automationSyncClient;

    public function __construct(CrmAbstractIntegration $integration) {
        parent::__construct($integration);
        $this->translator = $integration->getTranslator();

        // FIXME: to remove along with mock requests
        $this->client = new GuzzleHttp\Client();

        $this->loginClient = $this->makeClient(self::LOGIN_WS_PATH);
        $this->contactManagerClient = $this->makeClient(self::CONTACT_MANAGER_WS_PATH);
        $this->customFieldClient = $this->makeClient(self::CUSTOM_FIELD_WS_PATH);
        $this->automationSyncClient = $this->makeClient(self::AUTOMATION_SYNC_WS_PATH);
    }

    private function makeClient($path) {
        return new \SoapClient(self::ROOT_URL . $path . '?wsdl');
    }

    private function getSessionId() {
        // TODO: cache session ID

        $keys = $this->integration->getDecryptedApiKeys();

        try {
            $response = $this->loginClient->authenticationWs([
                'request' => $keys,
            ]);
        } catch (\SoapFault $e) {
            throw new ApiErrorException($this->translator->trans('mautic.ines_crm.form.invalid_identifiers'));
        }

        return $response->authenticationWsResult->idSession;
    }

    private function setAuthHeaders($client) {
        $sessionId = $this->getSessionId();

        $headers = new \SoapHeader('http://webservice.ines.fr', 'SessionID', ['ID' => $sessionId]);
        $client->__setSoapHeaders($headers);
    }

    public function getSyncInfo() {
        $client = $this->automationSyncClient;
        $this->setAuthHeaders($client);

        return $client->GetSyncInfo();
    }

    public function getClientCustomFields($internalRef) {
        $client = $this->customFieldClient;
        $this->setAuthHeaders($client);

        try {
            $response = $client->GetCompanyCF(['reference' => $internalRef]);
            self::cleanList($response->GetCompanyCFResult->Values->CustomField);
            return $response;
        } catch (\Exception $e) {
            dump($e);die();
        }
    }

    public function getContactCustomFields($internalRef) {
        $client = $this->customFieldClient;
        $this->setAuthHeaders($client);

        try {
            $response = $client->GetContactCF(['reference' => $internalRef]);
            self::cleanList($response->GetContactCFResult->Values->CustomField);
            return $response;
        } catch (\Exception $e) {
            dump($e);die();
        }
    }

    public function createClientCustomField($mappedData) {
        $client = $this->customFieldClient;
        $this->setAuthHeaders($client);

        try {
            return $client->InsertCompanyCF($mappedData);
        } catch (\Exception $e) {
            dump($e);die();
        }
    }

    public function updateClientCustomField($mappedData) {
        $client = $this->customFieldClient;
        $this->setAuthHeaders($client);

        try {
            return $client->UpdateCompanyCF($mappedData);
        } catch (\Exception $e) {
            dump($e);die();
        }
    }

    public function createContactCustomField($mappedData) {
        $client = $this->customFieldClient;
        $this->setAuthHeaders($client);

        try {
            return $client->InsertContactCF($mappedData);
        } catch (\Exception $e) {
            dump($e);die();
        }
    }

    public function updateContactCustomField($mappedData) {
        $client = $this->customFieldClient;
        $this->setAuthHeaders($client);

        try {
            return $client->UpdateContactCF($mappedData);
        } catch (\Exception $e) {
            dump($e);die();
        }
    }

    public function getClient($internalRef) {
        $client = $this->contactManagerClient;
        $this->setAuthHeaders($client);

        try {
            return $client->GetClient(['reference' => $internalRef]);
        } catch (\Exception $e) {
            dump($e);die();
        }
    }

    public function getContact($internalRef) {
        $client = $this->contactManagerClient;
        $this->setAuthHeaders($client);

        try {
            return $client->GetContact(['reference' => $internalRef]);
        } catch (\Exception $e) {
            dump($e);die();
        }
    }

    public function createClientWithContacts($mappedData) {
        $client = $this->automationSyncClient;
        $this->setAuthHeaders($client);

        try {
            return $client->AddClientWithContacts($mappedData);
        } catch (\Exception $e) {
            dump($e);die();
        }
    }

    public function createClient($mappedData) {
        $client = $this->contactManagerClient;
        $this->setAuthHeaders($client);

        try {
            return $client->AddClient($mappedData);
        } catch (\Exception $e) {
            dump($e);die();
        }
    }

    public function createContact($mappedData) {
        $client = $this->automationSyncClient;
        $this->setAuthHeaders($client);

        try {
            return $client->AddContact($mappedData);
        } catch (\Exception $e) {
            dump($e);die();
        }
    }

    // FIXME: To be removed or changed to `createClient`
    public function createCompany($mappedData) {
        $this->client->request('POST', 'http://localhost:4567/push_company', [
            'form_params' => $mappedData
        ]);
    }

    public function updateClient($inesClient) {
        $client = $this->contactManagerClient;
        $this->setAuthHeaders($client);

        try {
            return $client->UpdateClient(['client' => $inesClient]);
        } catch (\Exception $e) {
            dump($e);die();
        }
    }

    public function updateContact($inesContact) {
        $client = $this->contactManagerClient;
        $this->setAuthHeaders($client);

        try {
            return $client->UpdateContact(['contact' => $inesContact]);
        } catch (\Exception $e) {
            dump($e);die();
        }
    }

    private static function cleanList(&$dirtyList) {
        if (is_null($dirtyList)) {
            $dirtyList = [];
        } elseif (!is_array($dirtyList)) {
            $dirtyList = [$dirtyList];
        }
    }
}
