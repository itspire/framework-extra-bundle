<?php

/*
 * Copyright (c) 2016 - 2024 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Functional;

use Itspire\Common\Enum\Http\HttpMethod;
use Itspire\Common\Enum\Http\HttpResponseStatus;
use Itspire\Common\Enum\MimeType;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\RouterInterface;

class JsonControllerTest extends WebTestCase
{
    protected ?KernelBrowser $client = null;
    protected ?RouterInterface $router = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient(server: ['PHP_AUTH_USER' => 'test', 'PHP_AUTH_PW'   => 'password']);
        $this->router = static::$kernel->getContainer()->get(id: 'router');
    }

    public function tearDown(): void
    {
        unset($this->client, $this->router);

        parent::tearDown();
    }

    #[Test]
    public function indexTest(): void
    {
        // Do not indent JSON : it would cause test failure
        $bodyContent = <<<'JSON'
        {
            "testProperty": "test"
        }
        JSON;

        // Do not indent JSON : it would cause test failure
        $expectedContent = <<<'JSON'
        {
            "Query Param": "queryParam",
            "Request Param": 10,
            "Body Param": "test",
            "Header Param": "application/json"
        }
        JSON;

        $this->client->request(
            method: HttpMethod::POST->value,
            uri: $this->router->generate('jsonIndexTest', ['qParam' => 'queryParam']),
            parameters: ['rParam' => 10],
            server: [
                'CONTENT_TYPE' => MimeType::APPLICATION_JSON->value,
                'HTTP_ACCEPT' => MimeType::APPLICATION_JSON->value,
            ],
            content: $bodyContent
        );

        $response = $this->client->getResponse();

        static::assertEquals(expected: HttpResponseStatus::HTTP_OK->value, actual: $response->getStatusCode());
        static::assertEquals(expected: $expectedContent, actual: $response->getContent());
    }

    #[Test]
    public function inlineTest(): void
    {
        // Do not indent JSON : it would cause test failure
        $bodyContent = <<<'JSON'
        {
            "testProperty": "test"
        }
        JSON;

        // Do not indent JSON : it would cause test failure
        $expectedResult = <<<'JSON'
        {
            "Query Param": "queryParam2",
            "Request Param": 20,
            "Body Param": "test",
            "Header Param": "application/json"
        }
        JSON;

        $this->client->request(
            method: HttpMethod::POST->value,
            uri: $this->router->generate('jsonInlineTest', ['qParam' => 'queryParam2']),
            parameters: ['rParam' => 20],
            server: [
                'CONTENT_TYPE' => MimeType::APPLICATION_JSON->value,
                'HTTP_ACCEPT' => MimeType::APPLICATION_JSON->value,
            ],
            content: $bodyContent
        );

        $response = $this->client->getResponse();

        static::assertEquals(expected: HttpResponseStatus::HTTP_OK->value, actual: $response->getStatusCode());
        static::assertEquals(expected: $expectedResult, actual: $response->getContent());
    }

    #[Test]
    public function serializeTest(): void
    {
        // Do not indent JSON : it would cause test failure
        $expectedResult = <<<'JSON'
        {
            "testProperty": "testing"
        }
        JSON;

        $this->client->request(
            method: HttpMethod::GET->value,
            uri: $this->router->generate('jsonSerializeTest'),
            server: ['HTTP_ACCEPT' => MimeType::APPLICATION_JSON->value]
        );

        $response = $this->client->getResponse();

        static::assertEquals(expected: HttpResponseStatus::HTTP_OK->value, actual: $response->getStatusCode());
        static::assertEquals(expected: $expectedResult, actual: $response->getContent());
    }

    #[Test]
    public function exceptionTest(): void
    {
        // Do not indent JSON : it would cause test failure
        $expectedContent = <<<JSON
        {
            "code": "CONFLICT",
            "message": "itspire.exceptions.definitions.webservice.conflict",
            "details": []
        }
        JSON;

        $this->client->request(
            method: HttpMethod::GET->value,
            uri: $this->router->generate('jsonExceptionTest'),
            server: ['HTTP_ACCEPT' => MimeType::APPLICATION_JSON->value]
        );

        $response = $this->client->getResponse();

        static::assertEquals(expected: HttpResponseStatus::HTTP_CONFLICT->value, actual: $response->getStatusCode());
        static::assertEquals(expected: $expectedContent, actual: $response->getContent());
    }

    #[Test]
    public function exceptionWildcardAcceptTest(): void
    {
        // Do not indent JSON : it would cause test failure
        $expectedContent = <<<JSON
        {
            "code": "CONFLICT",
            "message": "itspire.exceptions.definitions.webservice.conflict",
            "details": []
        }
        JSON;

        $this->client->request(
            method: HttpMethod::GET->value,
            uri: $this->router->generate('jsonExceptionTest'),
            server: ['HTTP_ACCEPT' => 'application/*']
        );

        $response = $this->client->getResponse();

        static::assertEquals(expected: HttpResponseStatus::HTTP_CONFLICT->value, actual: $response->getStatusCode());
        static::assertEquals(expected: $expectedContent, actual: trim($response->getContent()));
        static::assertStringNotContainsString(needle: '<html lang="fr">', haystack: $response->getContent());
        static::assertStringNotContainsString(needle: '<pre lang="json">', haystack: $response->getContent());
        static::assertStringContainsString(needle: $expectedContent, haystack: $response->getContent());
    }

    #[Test]
    public function exceptionFullWildcardAcceptTest(): void
    {
        $this->client->request(
            method: HttpMethod::GET->value,
            uri: $this->router->generate('jsonExceptionTest', ['renderHtml' => true]),
            server: ['HTTP_ACCEPT' => '*/*']
        );

        $response = $this->client->getResponse();

        static::assertEquals(expected: HttpResponseStatus::HTTP_CONFLICT->value, actual: $response->getStatusCode());
        static::assertStringContainsString(needle: '<html lang="fr">', haystack: $response->getContent());
        static::assertStringContainsString(needle: '<pre lang="json">', haystack: $response->getContent());
        static::assertStringContainsString(
            needle: '&quot;code&quot;: &quot;CONFLICT&quot;',
            haystack: $response->getContent()
        );
        static::assertStringContainsString(
            needle: '&quot;message&quot;: &quot;itspire.exceptions.definitions.webservice.conflict&quot;',
            haystack: $response->getContent()
        );
        static::assertStringContainsString(needle: '&quot;details&quot;: []', haystack: $response->getContent());
        static::assertStringContainsString(needle: 'Symfony Web Debug Toolbar', haystack: $response->getContent());
    }

    #[Test]
    public function httpExceptionTest(): void
    {
        $this->client->request(
            method: HttpMethod::GET->value,
            uri: $this->router->generate('jsonHttpExceptionTest'),
            server: ['HTTP_ACCEPT' => MimeType::APPLICATION_JSON->value]
        );

        $response = $this->client->getResponse();

        static::assertEquals(expected: HttpResponseStatus::HTTP_BAD_REQUEST->value, actual: $response->getStatusCode());
        static::assertEquals(expected: '{}', actual: $response->getContent());
    }
}
