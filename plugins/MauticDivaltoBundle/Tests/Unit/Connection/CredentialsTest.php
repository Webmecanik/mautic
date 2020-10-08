<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDivaltoBundle\Tests\Unit\Connection;

use MauticPlugin\MauticDivaltoBundle\Connection\Credentials;

class CredentialsTest extends \PHPUnit\Framework\TestCase
{
    public function testGetters(): void
    {
        $userName     = 'foo';
        $password     = 'bar';

        $credentials = new Credentials($userName, $password);

        $this->assertEquals($userName, $credentials->getUserName());
        $this->assertEquals($password, $credentials->getPassword());
        $this->assertEquals('https://hello.company/authorize', $credentials->getAuthorizationUrl());
    }
}
