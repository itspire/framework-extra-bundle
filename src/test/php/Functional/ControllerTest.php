<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Functional;

use Itspire\Common\Enum\Http\HttpMethod;
use Itspire\Common\Enum\Http\HttpResponseStatus;
use Itspire\Common\Enum\MimeType;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\RouterInterface;

class ControllerTest extends WebTestCase
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

    /** @test */
    public function indexTest(): void
    {
        $expectedResult = [
            'Query Param' => 'queryParam',
            'Request Param' => 10,
            'Body Param' => 'test',
            'Header Param' => 'application/xml',
        ];

        $this->client->request(
            method: HttpMethod::POST->value,
            uri: $this->router->generate('indexTest', ['qParam' => 'queryParam']),
            parameters: ['rParam' => 10],
            server: [
                'CONTENT_TYPE' => MimeType::APPLICATION_XML->value,
                'HTTP_ACCEPT' => MimeType::APPLICATION_JSON->value,
            ],
            content: '<testObject testProperty="test" />'
        );

        $response = $this->client->getResponse();

        static::assertEquals(expected: HttpResponseStatus::HTTP_OK->value, actual: $response->getStatusCode());
        static::assertEquals(expected: json_encode($expectedResult), actual: $response->getContent());
    }

    /** @test */
    public function serializeTest(): void
    {
        $this->client->request(
            method: HttpMethod::GET->value,
            uri: $this->router->generate('serializeTest'),
            server: ['HTTP_ACCEPT' => MimeType::APPLICATION_XML->value]
        );

        $response = $this->client->getResponse();

        static::assertEquals(expected: HttpResponseStatus::HTTP_OK->value, actual: $response->getStatusCode());
        static::assertStringNotContainsString(needle: '<html lang="fr">', haystack: $response->getContent());
        static::assertStringContainsString(needle: 'testObject testProperty', haystack: $response->getContent());
        static::assertStringContainsString(needle: 'testing', haystack: $response->getContent());
    }

    /** @test */
    public function regularTest(): void
    {
        $this->client->request(method: HttpMethod::GET->value, uri: $this->router->generate('regularTest'));

        $response = $this->client->getResponse();

        static::assertEquals(expected: HttpResponseStatus::HTTP_OK->value, actual: $response->getStatusCode());
        static::assertEmpty(actual: $response->getContent());
    }

    /** @test */
    public function regularWithTemplateTest(): void
    {
        $this->client->request(method: HttpMethod::GET->value, uri: $this->router->generate('regularTemplateTest'));

        $response = $this->client->getResponse();

        static::assertEquals(expected: HttpResponseStatus::HTTP_OK->value, actual: $response->getStatusCode());
        static::assertStringContainsString(needle: '<pre lang="json">', haystack: $response->getContent());
        static::assertStringContainsString(needle: '[&quot;testWithTemplate&quot;]', haystack: $response->getContent());
        static::assertStringContainsString(needle: 'Symfony Web Debug Toolbar', haystack: $response->getContent());
    }

    /** @test */
    public function exceptionTest(): void
    {
        $this->client->request(
            method: HttpMethod::GET->value,
            uri: $this->router->generate('exceptionTest'),
            server: ['HTTP_ACCEPT' => MimeType::APPLICATION_JSON->value]
        );

        // Do not indent JSON : it would cause test failure
        $expectedContent = <<<JSON
        {
            "code": "CONFLICT",
            "message": "itspire.exceptions.definitions.webservice.conflict",
            "details": []
        }
        JSON;

        $response = $this->client->getResponse();

        static::assertEquals(expected: HttpResponseStatus::HTTP_CONFLICT->value, actual: $response->getStatusCode());
        static::assertEquals(expected: $expectedContent, actual: $response->getContent());
    }

    /** @test */
    public function exceptionWildcardAcceptTest(): void
    {
        $this->client->request(
            method: HttpMethod::GET->value,
            uri: $this->router->generate('exceptionTest'),
            server: ['HTTP_ACCEPT' => 'application/*']
        );

        $response = $this->client->getResponse();

        static::assertEquals(expected: HttpResponseStatus::HTTP_CONFLICT->value, actual: $response->getStatusCode());
        static::assertStringNotContainsString(needle: '<html lang="fr">', haystack: $response->getContent());
        static::assertStringContainsString(needle: 'ws_exception', haystack: $response->getContent());
        static::assertStringContainsString(needle: 'CONFLICT', haystack: $response->getContent());
    }

    /** @test */
    public function exceptionFullWildcardAcceptTest(): void
    {
        $this->client->request(
            method: HttpMethod::GET->value,
            uri: $this->router->generate('exceptionTest', ['renderHtml' => true]),
            server: ['HTTP_ACCEPT' => '*/*']
        );

        $response = $this->client->getResponse();

        static::assertEquals(expected: HttpResponseStatus::HTTP_CONFLICT->value, actual: $response->getStatusCode());
        static::assertStringContainsString(needle: '<html lang="fr">', haystack: $response->getContent());
        static::assertStringContainsString(needle: '<pre lang="xml">', haystack: $response->getContent());
        static::assertStringContainsString(needle: 'ws_exception', haystack: $response->getContent());
        static::assertStringContainsString(needle: 'CONFLICT', haystack: $response->getContent());
        static::assertStringContainsString(needle: 'Symfony Web Debug Toolbar', haystack: $response->getContent());
    }

    /** @test */
    public function httpExceptionTest(): void
    {
        $this->client->request(
            method: HttpMethod::GET->value,
            uri: $this->router->generate('httpExceptionTest'),
            server: ['HTTP_ACCEPT' => MimeType::APPLICATION_JSON->value]
        );

        $response = $this->client->getResponse();

        static::assertEquals(expected: HttpResponseStatus::HTTP_BAD_REQUEST->value, actual: $response->getStatusCode());
        static::assertEquals(expected:'{}', actual: $response->getContent());
    }

    /** @test */
    public function uploadTest(): void
    {
        $this->client->request(
            method: HttpMethod::POST->value,
            uri: $this->router->generate('uploadTest'),
            files: ['fParam' => new UploadedFile(realpath(__DIR__ . '/../../resources/test.txt'), 'myTest.txt')],
            server: ['HTTP_ACCEPT' => '*/*']
        );

        $response = $this->client->getResponse();

        static::assertEquals(expected: HttpResponseStatus::HTTP_OK->value, actual: $response->getStatusCode());
        static::assertEquals(
            expected: 'File Infos :<br/>{{myTest.txt}}<br/>{{txt}}<br/>{{10}}<br/>',
            actual: $response->getContent()
        );
    }

    /** @test */
    public function getFileTest(): void
    {
        $this->client->request(method: HttpMethod::GET->value, uri: $this->router->generate('getFileTest'));

        $expectedFile = new File(realpath(__DIR__ . '/../../resources/test.txt'));

        /** @var BinaryFileResponse $response */
        $response = $this->client->getResponse();

        static::assertEquals(expected: HttpResponseStatus::HTTP_OK->value, actual: $response->getStatusCode());
        static::assertEquals(expected: $expectedFile->getFileInfo(), actual: $response->getFile()->getFileInfo());
    }

    /** @test */
    public function securitySuccessTest(): void
    {
        $this->client->request(method: HttpMethod::GET->value, uri: $this->router->generate('securitySuccessTest'));

        $response = $this->client->getResponse();

        static::assertEquals(expected: HttpResponseStatus::HTTP_OK->value, actual: $response->getStatusCode());
        static::assertEquals(expected: 'success', actual: $response->getContent());
    }

    /** @test */
    public function securityFailTest(): void
    {
        $this->client->request(method: HttpMethod::GET->value, uri: $this->router->generate('securityFailTest'));

        $response = $this->client->getResponse();

        $httpResponseStatus = HttpResponseStatus::HTTP_FORBIDDEN;

        static::assertEquals(expected: $httpResponseStatus->value, actual: $response->getStatusCode());
        static::assertStringContainsString(
            needle: $httpResponseStatus->getDescription(),
            haystack: $response->getContent()
        );
    }

    /** @test */
    public function isGrantedSuccessTest(): void
    {
        $this->client->request(method: HttpMethod::GET->value, uri: $this->router->generate('isGrantedTest'));

        $response = $this->client->getResponse();

        static::assertEquals(expected: HttpResponseStatus::HTTP_OK->value, actual: $response->getStatusCode());
        static::assertEquals(expected: 'success', actual: $response->getContent());
    }

    /** @test */
    public function isGrantedFailTest(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

        $client->request(method: HttpMethod::GET->value, uri: $this->router->generate('isGrantedTest'));

        $response = $client->getResponse();

        $httpResponseStatus = HttpResponseStatus::HTTP_UNAUTHORIZED;

        static::assertEquals(expected: $httpResponseStatus->value, actual: $response->getStatusCode());
        static::assertStringContainsString(
            needle: sprintf('HTTP/1.1 %s %s', $httpResponseStatus->value, $httpResponseStatus->getDescription()),
            haystack: (string) $response
        );
    }
}
