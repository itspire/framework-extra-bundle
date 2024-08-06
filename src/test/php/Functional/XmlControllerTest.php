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

class XmlControllerTest extends WebTestCase
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
        // Do not indent XML : it would cause test failure
        $expectedContent = <<<'XML'
        <?xml version="1.0"?>
        <response>
          <item key="Query Param">queryParam</item>
          <item key="Request Param">10</item>
          <item key="Body Param">test</item>
          <item key="Header Param">application/xml</item>
        </response>
        XML;

        $this->client->request(
            method: HttpMethod::POST->value,
            uri: $this->router->generate('xmlIndexTest', ['qParam' => 'queryParam']),
            parameters: ['rParam' => 10],
            server: [
                'CONTENT_TYPE' => MimeType::APPLICATION_XML->value,
                'HTTP_ACCEPT' => MimeType::APPLICATION_XML->value,
            ],
            content: '<testObject testProperty="test" />'
        );

        $response = $this->client->getResponse();

        static::assertEquals(expected: HttpResponseStatus::HTTP_OK->value, actual: $response->getStatusCode());
        static::assertEquals(expected: $expectedContent, actual: trim($response->getContent()));
    }

    #[Test]
    public function inlineTest(): void
    {
        // Do not indent XML : it would cause test failure
        $expectedContent = <<<'XML'
        <?xml version="1.0"?>
        <response>
          <item key="Query Param">queryParam2</item>
          <item key="Request Param">20</item>
          <item key="Body Param">test</item>
          <item key="Header Param">application/xml</item>
        </response>
        XML;

        $this->client->request(
            method: HttpMethod::POST->value,
            uri: $this->router->generate('xmlInlineTest', ['qParam' => 'queryParam2']),
            parameters: ['rParam' => 20],
            server: [
                'CONTENT_TYPE' => MimeType::APPLICATION_XML->value,
                'HTTP_ACCEPT' => MimeType::APPLICATION_XML->value,
            ],
            content: '<testObject testProperty="test" />'
        );

        $response = $this->client->getResponse();

        static::assertEquals(expected: HttpResponseStatus::HTTP_OK->value, actual: $response->getStatusCode());
        static::assertEquals(expected: $expectedContent, actual: trim($response->getContent()));
    }

    #[Test]
    public function serializeTest(): void
    {
        // Do not indent XML : it would cause test failure
        $expectedContent = <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <testObject testProperty="testing"/>
        XML;

        $this->client->request(
            method: HttpMethod::GET->value,
            uri: $this->router->generate('xmlSerializeTest'),
            server: ['HTTP_ACCEPT' => MimeType::APPLICATION_XML->value]
        );

        $response = $this->client->getResponse();

        static::assertEquals(expected: HttpResponseStatus::HTTP_OK->value, actual: $response->getStatusCode());
        static::assertEquals(expected: $expectedContent, actual: trim($response->getContent()));
    }

    #[Test]
    public function exceptionTest(): void
    {
        // Do not indent XML : it would cause test failure
        $expectedContent = <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <ws_exception code="CONFLICT" message="itspire.exceptions.definitions.webservice.conflict"/>
        XML;

        $this->client->request(
            method: HttpMethod::GET->value,
            uri: $this->router->generate('xmlExceptionTest'),
            server: ['HTTP_ACCEPT' => MimeType::APPLICATION_XML->value]
        );

        $response = $this->client->getResponse();

        static::assertEquals(expected: HttpResponseStatus::HTTP_CONFLICT->value, actual: $response->getStatusCode());
        static::assertEquals(expected: $expectedContent, actual: trim($response->getContent()));
    }

    #[Test]
    public function exceptionWildcardAcceptTest(): void
    {
        // Do not indent XML : it would cause test failure
        $expectedContent = <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <ws_exception code="CONFLICT" message="itspire.exceptions.definitions.webservice.conflict"/>
        XML;

        $this->client->request(
            method: HttpMethod::GET->value,
            uri: $this->router->generate('xmlExceptionTest'),
            server: ['HTTP_ACCEPT' => 'application/*']
        );

        $response = $this->client->getResponse();

        static::assertEquals(expected: HttpResponseStatus::HTTP_CONFLICT->value, actual: $response->getStatusCode());
        static::assertEquals(expected: $expectedContent, actual: trim($response->getContent()));
        static::assertStringNotContainsString(needle: '<html lang="fr">', haystack: $response->getContent());
        static::assertStringNotContainsString(needle: '<pre lang="xml">', haystack: $response->getContent());
        static::assertStringContainsString(needle: $expectedContent, haystack: $response->getContent());
    }

    #[Test]
    public function exceptionFullWildcardAcceptTest(): void
    {
        $this->client->request(
            method: HttpMethod::GET->value,
            uri: $this->router->generate('xmlExceptionTest', ['renderHtml' => true]),
            server: ['HTTP_ACCEPT' => '*/*']
        );

        $response = $this->client->getResponse();

        static::assertEquals(expected: HttpResponseStatus::HTTP_CONFLICT->value, actual: $response->getStatusCode());
        static::assertStringContainsString(needle: '<html lang="fr">', haystack: $response->getContent());
        static::assertStringContainsString(needle: '<pre lang="xml">', haystack: $response->getContent());
        static::assertStringContainsString(
            needle: '&lt;ws_exception code=&quot;CONFLICT&quot; message=',
            haystack: $response->getContent()
        );
        static::assertStringContainsString(
            needle: 'CONFLICT&quot; message=&quot;itspire.exceptions.definitions.webservice.conflict&quot;/&gt;',
            haystack: $response->getContent()
        );
        static::assertStringContainsString(needle: 'Symfony Web Debug Toolbar', haystack: $response->getContent());
    }

    #[Test]
    public function httpExceptionTest(): void
    {
        $this->client->request(
            method: HttpMethod::GET->value,
            uri: $this->router->generate('xmlHttpExceptionTest'),
            server: ['HTTP_ACCEPT' => MimeType::APPLICATION_XML->value]
        );

        $response = $this->client->getResponse();

        static::assertEquals(expected: HttpResponseStatus::HTTP_BAD_REQUEST->value, actual: $response->getStatusCode());
        static::assertEquals(expected: '', actual: $response->getContent());
    }
}
