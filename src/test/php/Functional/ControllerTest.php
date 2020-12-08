<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Functional;

use Itspire\Common\Enum\MimeType;
use Itspire\Http\Common\Enum\HttpMethod;
use Itspire\Http\Common\Enum\HttpResponseStatus;
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

        $this->client = static::createClient();
        $this->router = static::$kernel->getContainer()->get('router');
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

        $expectedContent = json_encode($expectedResult);

        $this->client->request(
            HttpMethod::POST,
            $this->router->generate('indexTest', ['qParam' => 'queryParam']),
            ['rParam' => 10],
            [],
            ['CONTENT_TYPE' => MimeType::APPLICATION_XML, 'HTTP_ACCEPT' => MimeType::APPLICATION_JSON],
            '<testObject testProperty="test" />'
        );

        $response = $this->client->getResponse();

        static::assertEquals(HttpResponseStatus::HTTP_OK[0], $response->getStatusCode());
        static::assertEquals($expectedContent, $response->getContent());
    }

    /** @test */
    public function serializeTest(): void
    {
        $this->client->request(
            HttpMethod::GET,
            $this->router->generate('serializeTest'),
            [],
            [],
            ['HTTP_ACCEPT' => MimeType::APPLICATION_XML]
        );

        $response = $this->client->getResponse();

        static::assertEquals(HttpResponseStatus::HTTP_OK[0], $response->getStatusCode());
        static::assertStringNotContainsString('<html lang="fr">', $response->getContent());
        static::assertStringContainsString('testObject testProperty', $response->getContent());
        static::assertStringContainsString('testing', $response->getContent());
    }

    /** @test */
    public function regularTest(): void
    {
        $this->client->request(HttpMethod::GET, $this->router->generate('regularTest'));

        $response = $this->client->getResponse();

        static::assertEquals(HttpResponseStatus::HTTP_OK[0], $response->getStatusCode());
        static::assertEmpty($response->getContent());
    }

    /** @test */
    public function regularWithTemplateTest(): void
    {
        $this->client->request(HttpMethod::GET, $this->router->generate('regularTemplateTest'));

        $response = $this->client->getResponse();

        static::assertEquals(HttpResponseStatus::HTTP_OK[0], $response->getStatusCode());
        static::assertStringContainsString('<pre lang="json">', $response->getContent());
        static::assertStringContainsString('[&quot;test&quot;]', $response->getContent());
        static::assertStringContainsString('Symfony Web Debug Toolbar', $response->getContent());
    }

    /** @test */
    public function exceptionTest(): void
    {
        $this->client->request(
            HttpMethod::GET,
            $this->router->generate('exceptionTest'),
            [],
            [],
            ['HTTP_ACCEPT' => MimeType::APPLICATION_JSON]
        );

        $expectedContent = <<<JSON
        {
            "code": "TRANSFORMATION_ERROR",
            "message": "itspire.exceptions.adapter_transformation_error",
            "details": []
        }
        JSON;

        $response = $this->client->getResponse();

        static::assertEquals(HttpResponseStatus::HTTP_INTERNAL_SERVER_ERROR[0], $response->getStatusCode());
        static::assertEquals($expectedContent, $response->getContent());
    }

    /** @test */
    public function exceptionWildcardAcceptTest(): void
    {
        $this->client->request(
            HttpMethod::GET,
            $this->router->generate('exceptionTest'),
            [],
            [],
            ['HTTP_ACCEPT' => 'application/*']
        );

        $response = $this->client->getResponse();

        static::assertEquals(HttpResponseStatus::HTTP_INTERNAL_SERVER_ERROR[0], $response->getStatusCode());
        static::assertStringNotContainsString('<html lang="fr">', $response->getContent());
        static::assertStringContainsString('webservice_exception', $response->getContent());
        static::assertStringContainsString('TRANSFORMATION_ERROR', $response->getContent());
    }

    /** @test */
    public function exceptionFullWildcardAcceptTest(): void
    {
        $this->client->request(
            HttpMethod::GET,
            $this->router->generate('exceptionTest', ['renderHtml' => true]),
            [],
            [],
            ['HTTP_ACCEPT' => '*/*']
        );

        $response = $this->client->getResponse();

        static::assertEquals(HttpResponseStatus::HTTP_INTERNAL_SERVER_ERROR[0], $response->getStatusCode());
        static::assertStringContainsString('<html lang="fr">', $response->getContent());
        static::assertStringContainsString('<pre lang="xml">', $response->getContent());
        static::assertStringContainsString('webservice_exception', $response->getContent());
        static::assertStringContainsString('TRANSFORMATION_ERROR', $response->getContent());
        static::assertStringContainsString('Symfony Web Debug Toolbar', $response->getContent());
    }

    /** @test */
    public function uploadTest(): void
    {
        $this->client->request(
            HttpMethod::POST,
            $this->router->generate('uploadTest'),
            [],
            ['fParam' => new UploadedFile(realpath(__DIR__ . '/../../resources/test.txt'), 'myTest.txt')],
            ['HTTP_ACCEPT' => '*/*']
        );

        $response = $this->client->getResponse();

        static::assertEquals(HttpResponseStatus::HTTP_OK[0], $response->getStatusCode());
        static::assertEquals('File Infos :<br/>{{myTest.txt}}<br/>{{txt}}<br/>{{10}}<br/>', $response->getContent());
    }

    /** @test */
    public function getFileTest(): void
    {
        $this->client->request(HttpMethod::GET, $this->router->generate('getFileTest'));

        $expectedFile = new File(realpath(__DIR__ . '/../../resources/test.txt'));

        /** @var BinaryFileResponse $response */
        $response = $this->client->getResponse();

        static::assertEquals(HttpResponseStatus::HTTP_OK[0], $response->getStatusCode());
        static::assertEquals($expectedFile->getFileInfo(), $response->getFile()->getFileInfo());
    }

    /** @test */
    public function securitySuccessTest(): void
    {
        $this->client->request(HttpMethod::GET, $this->router->generate('securitySuccessTest'));

        $response = $this->client->getResponse();

        static::assertEquals(HttpResponseStatus::HTTP_OK[0], $this->client->getResponse()->getStatusCode());
        static::assertEquals('success', $response->getContent());
    }

    /** @test */
    public function securityFailTest(): void
    {
        $this->client->request(HttpMethod::GET, $this->router->generate('securityFailTest'));

        $response = $this->client->getResponse();

        static::assertEquals(HttpResponseStatus::HTTP_FORBIDDEN[0], $this->client->getResponse()->getStatusCode());
        static::assertStringContainsString(HttpResponseStatus::HTTP_FORBIDDEN[1], $response->getContent());
    }
}
