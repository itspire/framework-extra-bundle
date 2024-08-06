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

class IsGrantedControllerTest extends WebTestCase
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
    public function isGrantedSuccessTest(): void
    {
        $this->client->request(method: HttpMethod::GET->value, uri: $this->router->generate('securityIsGrantedTest'));

        $response = $this->client->getResponse();

        static::assertEquals(expected: HttpResponseStatus::HTTP_OK->value, actual: $response->getStatusCode());
        static::assertEquals(expected: 'success', actual: $response->getContent());
    }

    #[Test]
    public function isGrantedFailTest(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

        $client->request(method: HttpMethod::GET->value, uri: $this->router->generate('securityIsGrantedTest'));

        $response = $client->getResponse();

        $httpResponseStatus = HttpResponseStatus::HTTP_UNAUTHORIZED;

        static::assertEquals(expected: $httpResponseStatus->value, actual: $response->getStatusCode());
        static::assertStringContainsString(
            needle: sprintf('HTTP/1.1 %s %s', $httpResponseStatus->value, $httpResponseStatus->getDescription()),
            haystack: (string) $response
        );
    }
}
