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

class SecurityFailControllerTest extends WebTestCase
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
    public function securitySuccessTest(): void
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
}
