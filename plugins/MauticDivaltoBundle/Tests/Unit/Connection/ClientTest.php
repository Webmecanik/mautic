<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDivaltoBundle\Tests\Unit\Connection;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mautic\IntegrationsBundle\Auth\Provider\Oauth2TwoLegged\HttpFactory;
use Mautic\IntegrationsBundle\Exception\PluginNotConfiguredException;
use MauticPlugin\MauticDivaltoBundle\Connection\Client;
use MauticPlugin\MauticDivaltoBundle\Connection\Config as ConnectionConfig;
use MauticPlugin\MauticDivaltoBundle\Integration\Config;
use Monolog\Logger;

class ClientTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var HttpFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $httpFactory;

    /**
     * @var Config|\PHPUnit_Framework_MockObject_MockObject
     */
    private $config;

    /**
     * @var ConnectionConfig|\PHPUnit_Framework_MockObject_MockObject
     */
    private $connectionConfig;

    /**
     * @var Logger|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var \MauticPlugin\MauticDivaltoBundle\Connection\Client
     */
    private $client;

    protected function setUp(): void
    {
        $this->httpFactory = $this->createMock(HttpFactory::class);
        $this->config      = $this->createMock(Config::class);
        $this->config->method('getApiKeys')
            ->willReturn(
                [
                    'username'     => 'foo',
                    'password'     => 'bar',
                ]
            );

        $this->connectionConfig = $this->createMock(ConnectionConfig::class);
        $this->logger           = $this->createMock(Logger::class);
        $this->client           = new Client($this->httpFactory, $this->config, $this->connectionConfig, $this->logger);
    }

    public function testGetRequestIsPreparedAsExpectedAndObjectsAreReturned(): void
    {
        $object        = 'contact';
        $startDateTime = new \DateTime('-5 days');
        $endDateTime   = new \DateTime();
        $page          = 1;
        $results       = json_decode(file_get_contents(__DIR__.'/json/contacts.json'), true);

        $client = new GuzzleClient(
            [
                'handler' => new MockHandler(
                    [
                        function (Request $request, array $options) use ($object, $startDateTime, $endDateTime, $page, $results) {
                            $this->assertEquals(
                                sprintf(
                                    '/api/%s?createdOrModifiedSince=%s&createdOrModifiedBefore=%s&page=%s',
                                    $object,
                                    $startDateTime->getTimestamp(),
                                    $endDateTime->getTimestamp(),
                                    $page
                                ),
                                $request->getRequestTarget()
                            );
                            $this->assertEquals('GET', $request->getMethod());

                            return new Response(
                                200,
                                ['Content-Type' => 'application/json; charset=UTF-8'],
                                json_encode($results)
                            );
                        },
                    ]
                ),
            ]
        );

        $this->config->expects($this->once())
            ->method('isConfigured')
            ->willReturn(true);

        $this->httpFactory->expects($this->once())
            ->method('getClient')
            ->willReturn($client);

        $this->logger->expects($this->never())
            ->method('error');

        $returnedResults = $this->client->get($object, $startDateTime, $endDateTime, $page);

        $this->assertTrue(!empty($returnedResults));
        $this->assertEquals($results, $returnedResults);
    }

    public function testGetRequestLogsErrorIfResponseCodeIsNot200(): void
    {
        $object        = 'contact';
        $startDateTime = new \DateTime('-5 days');
        $endDateTime   = new \DateTime();
        $page          = 1;

        $client = new GuzzleClient(
            [
                'handler' => new MockHandler(
                    [
                        function (Request $request, array $options) use ($object, $startDateTime, $endDateTime, $page) {
                            $this->assertEquals(
                                sprintf(
                                    '/api/%s?createdOrModifiedSince=%s&createdOrModifiedBefore=%s&page=%s',
                                    $object,
                                    $startDateTime->getTimestamp(),
                                    $endDateTime->getTimestamp(),
                                    $page
                                ),
                                $request->getRequestTarget()
                            );
                            $this->assertEquals('GET', $request->getMethod());

                            return new Response(500);
                        },
                    ]
                ),
            ]
        );

        $this->config->expects($this->once())
            ->method('isConfigured')
            ->willReturn(true);

        $this->httpFactory->expects($this->once())
            ->method('getClient')
            ->willReturn($client);

        $this->logger->expects($this->once())
            ->method('error');

        $returnedResults = $this->client->get($object, $startDateTime, $endDateTime, $page);

        $this->assertEquals([], $returnedResults);
    }

    public function testGetFieldsRequestIsPreparedAsExpectedAndFieldsAreReturned(): void
    {
        $object  = 'contact';
        $results = json_decode(file_get_contents(__DIR__.'/json/contacts_fields.json'), true);

        $client = new GuzzleClient(
            [
                'handler' => new MockHandler(
                    [
                        function (Request $request, array $options) use ($object, $results) {
                            $this->assertEquals(
                                sprintf(
                                    '/api/fields/%s',
                                    $object
                                ),
                                $request->getRequestTarget()
                            );
                            $this->assertEquals('GET', $request->getMethod());

                            return new Response(
                                200,
                                ['Content-Type' => 'application/json; charset=UTF-8'],
                                json_encode($results)
                            );
                        },
                    ]
                ),
            ]
        );

        $this->config->expects($this->once())
            ->method('isConfigured')
            ->willReturn(true);

        $this->httpFactory->expects($this->once())
            ->method('getClient')
            ->willReturn($client);

        $this->logger->expects($this->never())
            ->method('error');

        $returnedResults = $this->client->getFields($object);

        $this->assertTrue(!empty($returnedResults));
        $this->assertEquals($results, $returnedResults);
    }

    public function testGetFieldsLogsErrorIfResponseCodeIsNot200(): void
    {
        $object = 'contact';

        $client = new GuzzleClient(
            [
                'handler' => new MockHandler(
                    [
                        function (Request $request, array $options) use ($object) {
                            $this->assertEquals(
                                sprintf(
                                    '/api/fields/%s',
                                    $object
                                ),
                                $request->getRequestTarget()
                            );
                            $this->assertEquals('GET', $request->getMethod());

                            return new Response(500);
                        },
                    ]
                ),
            ]
        );

        $this->config->expects($this->once())
            ->method('isConfigured')
            ->willReturn(true);

        $this->httpFactory->expects($this->once())
            ->method('getClient')
            ->willReturn($client);

        $this->logger->expects($this->once())
            ->method('error');

        $returnedResults = $this->client->getFields($object);

        $this->assertEquals([], $returnedResults);
    }

    public function testUpsertRequestIsPreparedAsExpectedAndResponseReturned(): void
    {
        $object   = 'contact';
        $results  = json_decode(file_get_contents(__DIR__.'/json/contacts_upsert.json'), true);
        $contacts = json_decode(file_get_contents(__DIR__.'/json/contacts.json'), true);

        $client = new GuzzleClient(
            [
                'handler' => new MockHandler(
                    [
                        function (Request $request, array $options) use ($object, $results) {
                            $this->assertEquals(
                                sprintf(
                                    '/api/%s',
                                    $object
                                ),
                                $request->getRequestTarget()
                            );
                            $this->assertEquals('POST', $request->getMethod());

                            return new Response(
                                200,
                                ['Content-Type' => 'application/json; charset=UTF-8'],
                                json_encode($results)
                            );
                        },
                    ]
                ),
            ]
        );

        $this->config->expects($this->once())
            ->method('isConfigured')
            ->willReturn(true);

        $this->httpFactory->expects($this->once())
            ->method('getClient')
            ->willReturn($client);

        $this->logger->expects($this->never())
            ->method('error');

        $returnedResults = $this->client->upsert($object, $contacts);

        $this->assertTrue(!empty($returnedResults));
        $this->assertEquals($results, $returnedResults);
    }

    public function testUpsertLogsErrorIfResponseIsNot200(): void
    {
        $object   = 'contact';
        $contacts = json_decode(file_get_contents(__DIR__.'/json/contacts.json'), true);

        $client = new GuzzleClient(
            [
                'handler' => new MockHandler(
                    [
                        function (Request $request, array $options) use ($object) {
                            $this->assertEquals(
                                sprintf(
                                    '/api/%s',
                                    $object
                                ),
                                $request->getRequestTarget()
                            );
                            $this->assertEquals('POST', $request->getMethod());

                            return new Response(500);
                        },
                    ]
                ),
            ]
        );

        $this->config->expects($this->once())
            ->method('isConfigured')
            ->willReturn(true);

        $this->httpFactory->expects($this->once())
            ->method('getClient')
            ->willReturn($client);

        $this->logger->expects($this->once())
            ->method('error');

        $returnedResults = $this->client->upsert($object, $contacts);

        $this->assertEquals([], $returnedResults);
    }

    public function testPluginNotConfiguredExceptionIsThrownIfNotConfigured(): void
    {
        $this->expectException(PluginNotConfiguredException::class);

        $this->config->expects($this->once())
            ->method('isConfigured')
            ->willReturn(false);

        $this->httpFactory->expects($this->never())
            ->method('getClient');

        $this->client->getFields('contact');
    }
}
