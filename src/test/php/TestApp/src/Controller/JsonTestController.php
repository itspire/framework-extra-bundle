<?php

/*
 * Copyright (c) 2016 - 2024 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\TestApp\Controller;

use Itspire\Common\Enum\Http\HttpMethod;
use Itspire\Common\Enum\Http\HttpResponseStatus;
use Itspire\Common\Enum\MimeType;
use Itspire\Exception\Definition\Http\HttpExceptionDefinition;
use Itspire\Exception\Definition\Webservice\WebserviceExceptionDefinition;
use Itspire\Exception\Http\HttpException;
use Itspire\Exception\Webservice\WebserviceException;
use Itspire\FrameworkExtraBundle\Attribute as ItspireFrameworkExtra;
use Itspire\FrameworkExtraBundle\Tests\TestApp\Enum\Role;
use Itspire\FrameworkExtraBundle\Tests\TestApp\Model\TestObject;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\SerializerInterface;

#[Route(path: '/json')]
#[ItspireFrameworkExtra\Produces([MimeType::APPLICATION_JSON])]
class JsonTestController extends AbstractController
{
    #[Route(path: '/index', name: 'jsonIndexTest', methods: [Request::METHOD_POST])]
    #[ItspireFrameworkExtra\Consumes([MimeType::APPLICATION_JSON])]
    #[ItspireFrameworkExtra\HeaderParam(name: 'contentType', headerName: 'Content-Type', type: 'string')]
    #[ItspireFrameworkExtra\QueryParam(name: 'qParam', requirements: '\w+')]
    #[ItspireFrameworkExtra\RequestParam(name: 'rParam', type: 'int', requirements: '\d+')]
    #[ItspireFrameworkExtra\BodyParam(name: 'bParam', class: TestObject::class)]
    public function index(
        ?string $contentType = null,
        ?string $qParam = null,
        $rParam = null,
        ?TestObject $bParam = null
    ): Response {
        $result = [
            'Query Param' => $qParam,
            'Request Param' => $rParam,
            'Body Param' => $bParam->getTestProperty(),
            'Header Param' => $contentType,
        ];

        return $this->json(
            $result,
            context: [
                JsonEncode::OPTIONS => \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_PRESERVE_ZERO_FRACTION,
            ]
        );
    }

    #[Route(path: '/inline', name: 'jsonInlineTest', methods: [Request::METHOD_POST])]
    #[ItspireFrameworkExtra\Consumes([MimeType::APPLICATION_JSON])]
    public function inline(
        SerializerInterface $serializer,
        #[ItspireFrameworkExtra\HeaderParam(headerName: 'Content-Type')]
        string $contentType,
        #[ItspireFrameworkExtra\RequestParam]
        int $rParam,
        #[ItspireFrameworkExtra\BodyParam]
        TestObject $bParam,
        #[ItspireFrameworkExtra\QueryParam(requirements: '\w+')]
        ?string $qParam = null
    ): Response {
        $result = [
            'Query Param' => $qParam,
            'Request Param' => $rParam,
            'Body Param' => $bParam->getTestProperty(),
            'Header Param' => $contentType,
        ];

        return new Response(
            $serializer->serialize(
                $result,
                'json',
                [JsonEncode::OPTIONS => \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_PRESERVE_ZERO_FRACTION]
            ),
            HttpResponseStatus::HTTP_OK->value,
            ['Content-Type' => MimeType::APPLICATION_JSON->value]
        );
    }

    #[ItspireFrameworkExtra\Route(path: '/serialize', name: 'jsonSerializeTest', methods: [Request::METHOD_GET])]
    public function serialize(): TestObject
    {
        $testObject = new TestObject();
        $testObject->setTestProperty('testing');

        return $testObject;
    }

    #[ItspireFrameworkExtra\Route(path: '/exception', name: 'jsonExceptionTest', methods: HttpMethod::GET)]
    public function exception(): void
    {
        throw new WebserviceException(WebserviceExceptionDefinition::CONFLICT);
    }

    #[ItspireFrameworkExtra\Route(path: '/httpException', name: 'jsonHttpExceptionTest', methods: HttpMethod::GET)]
    public function httpException(): void
    {
        throw new HttpException(HttpExceptionDefinition::HTTP_BAD_REQUEST);
    }
}
