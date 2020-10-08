<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDivaltoBundle\Connection;

use GuzzleHttp\Handler\MockHandler;
use function GuzzleHttp\Psr7\parse_query;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

class MockedHandler extends MockHandler
{
    public function __invoke(RequestInterface $request, array $options)
    {
        return $this->getResponse($request);
    }

    private function getResponse(RequestInterface $request): Response
    {
        $path   = $request->getUri()->getPath();
        $method = $request->getMethod();

        switch ($path) {
            case '/api/contact':
                if ('GET' === $method) {
                    $page = (int) parse_query($request->getUri()->getQuery())['page'];

                    return $this->getContacts($page);
                }

                if ('POST' === $method) {
                    $objects = json_decode($request->getBody()->getContents(), true);

                    return $this->getUpsertResponse($objects);
                }

                throw new \Exception('invalid method');
            case '/api/fields/contact':
                return $this->getFields('contacts');
            case '/api/fields/company':
                return $this->getFields('companys');
            case '/api/company':
                if ('GET' === $method) {
                    $page = (int) parse_query($request->getUri()->getQuery())['page'];

                    return $this->getCompanys($page);
                }

                if ('POST' === $method) {
                    $objects = json_decode($request->getBody()->getContents(), true);

                    return $this->getUpsertResponse($objects);
                }

                throw new \Exception('invalid method');
        }

        throw new \Exception(sprintf('%s is not supported for method %s', $path, $method));
    }

    private function getContacts(int $page): Response
    {
        $results = 1 === $page
            ?
            file_get_contents(__DIR__.'/../Tests/Unit/Connection/json/contacts.json')
            :
            '[]';

        return new Response(
            200,
            ['Content-Type' => 'application/json; charset=UTF-8'],
            $results
        );
    }

    private function getCompanys(int $page): Response
    {
        $results = 1 === $page
            ?
            file_get_contents(__DIR__.'/../Tests/Unit/Connection/json/companys.json')
            :
            '[]';

        return new Response(
            200,
            ['Content-Type' => 'application/json; charset=UTF-8'],
            $results
        );
    }

    private function getUpsertResponse(array $objects): Response
    {
        $results = [];
        foreach ($objects as $object) {
            $isUpdate  = isset($object['id']);
            $results[] = [
                'id'       => $object['id'] ?? uniqid(),
                'code'     => $isUpdate ? 200 : 201,
                'message'  => $isUpdate ? 'Object updated' : 'Object created',
                'metadata' => $object['metadata'],
            ];
        }

        return new Response(
            200,
            ['Content-Type' => 'application/json; charset=UTF-8'],
            json_encode($results)
        );
    }

    private function getFields(string $object): Response
    {
        $results = file_get_contents(sprintf(__DIR__.'/../Tests/Unit/Connection/json/%s_fields.json', $object));

        return new Response(
            200,
            ['Content-Type' => 'application/json; charset=UTF-8'],
            $results
        );
    }
}
