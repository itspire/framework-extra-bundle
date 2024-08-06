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
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\SerializerInterface;

#[Route(path: '/xml')]
#[ItspireFrameworkExtra\Produces([MimeType::APPLICATION_XML])]
class XmlTestController extends AbstractController
{
    #[Route(path: '/index', name: 'xmlIndexTest', methods: [Request::METHOD_POST])]
    #[ItspireFrameworkExtra\Consumes([MimeType::APPLICATION_XML])]
    #[ItspireFrameworkExtra\HeaderParam(name: 'contentType', headerName: 'Content-Type', type: 'string')]
    #[ItspireFrameworkExtra\QueryParam(name: 'qParam', requirements: '\w+')]
    #[ItspireFrameworkExtra\RequestParam(name: 'rParam', type: 'int', requirements: '\d+')]
    #[ItspireFrameworkExtra\BodyParam(name: 'bParam', class: TestObject::class)]
    public function index(
        SerializerInterface $serializer,
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

        return new Response(
            $serializer->serialize($result, 'xml', [XmlEncoder::FORMAT_OUTPUT => true]),
            HttpResponseStatus::HTTP_OK->value,
            ['Content-Type' => MimeType::APPLICATION_XML->value]
        );
    }

    #[Route(path: '/inline', name: 'xmlInlineTest', methods: [Request::METHOD_POST])]
    #[ItspireFrameworkExtra\Consumes([MimeType::APPLICATION_XML])]
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
            $serializer->serialize($result, 'xml', [XmlEncoder::FORMAT_OUTPUT => true]),
            HttpResponseStatus::HTTP_OK->value,
            ['Content-Type' => MimeType::APPLICATION_XML->value]
        );
    }

    #[ItspireFrameworkExtra\Route(path: '/serialize', name: 'xmlSerializeTest', methods: [Request::METHOD_GET])]
    public function serialize(): TestObject
    {
        $testObject = new TestObject();
        $testObject->setTestProperty('testing');

        return $testObject;
    }

    #[ItspireFrameworkExtra\Route(path: '/exception', name: 'xmlExceptionTest', methods: HttpMethod::GET)]
    public function exception(): void
    {
        throw new WebserviceException(WebserviceExceptionDefinition::CONFLICT);
    }

    #[ItspireFrameworkExtra\Route(path: '/httpException', name: 'xmlHttpExceptionTest', methods: HttpMethod::GET)]
    public function httpException(): void
    {
        throw new HttpException(HttpExceptionDefinition::HTTP_BAD_REQUEST);
    }
}
