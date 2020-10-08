<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDivaltoBundle\Connection;

use Mautic\IntegrationsBundle\Auth\Provider\Oauth2TwoLegged\Credentials\PasswordCredentialsGrantInterface;

class Credentials implements PasswordCredentialsGrantInterface
{
    /**
     * @var string|null
     */
    private $userName;

    /**
     * @var string|null
     */
    private $password;

    /**
     * @var string|null
     */
    private $projectCode;

    public function __construct(string $userName, string $password, string $projectCode)
    {
        $this->userName    = $userName;
        $this->password    = $password;
        $this->projectCode = $projectCode;
    }

    public function getAuthorizationUrl(): string
    {
        return 'https://api.weavy.divalto.com/v1/Authenticate';
    }

    public function getUserName(): ?string
    {
        return $this->userName;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getProjectCode(): ?string
    {
        return $this->projectCode;
    }

    public function getClientId(): ?string
    {
        return '1';
    }

    public function getClientSecret(): ?string
    {
        return '1';
    }
}
