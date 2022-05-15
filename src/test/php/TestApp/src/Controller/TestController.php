<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
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
use Itspire\FrameworkExtraBundle\Tests\TestApp\Model\TestObject;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends AbstractController
{
    #[Route(path: '/index', name: 'indexTest', methods: [Request::METHOD_POST])]
    #[ItspireFrameworkExtra\Consumes([MimeType::APPLICATION_XML])]
    #[ItspireFrameworkExtra\Produces([MimeType::APPLICATION_JSON])]
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

        return new Response(
            json_encode($result),
            HttpResponseStatus::HTTP_OK->value,
            ['Content-Type' => MimeType::APPLICATION_JSON->value]
        );
    }

    #[ItspireFrameworkExtra\Route(path: '/serialize', name: 'serializeTest', methods: [Request::METHOD_GET])]
    #[ItspireFrameworkExtra\Produces([MimeType::APPLICATION_XML])]
    public function serialize(): TestObject
    {
        $testObject = new TestObject();
        $testObject->setTestProperty('testing');

        return $testObject;
    }

    #[ItspireFrameworkExtra\Route(path: '/regular', name: 'regularTest', methods: [HttpMethod::GET])]
    public function regular(): array
    {
        return ['testRegular'];
    }

    #[ItspireFrameworkExtra\Route(
        path: '/regularWithTemplate',
        name: 'regularTemplateTest',
        methods: [HttpMethod::GET]
    )]
    #[Template('@ItspireFrameworkExtra/response.html.twig')]
    public function regularWithTemplate(): array
    {
        return ['controllerResult' => json_encode(['testWithTemplate']), 'format' => 'json'];
    }

    #[ItspireFrameworkExtra\Route(path: '/exception', name: 'exceptionTest', methods: [HttpMethod::GET])]
    #[ItspireFrameworkExtra\Produces([MimeType::APPLICATION_XML, MimeType::APPLICATION_JSON])]
    public function exception(): void
    {
        throw new WebserviceException(WebserviceExceptionDefinition::CONFLICT);
    }

    #[ItspireFrameworkExtra\Route(path: '/httpException', name: 'httpExceptionTest', methods: [HttpMethod::GET])]
    #[ItspireFrameworkExtra\Produces([MimeType::APPLICATION_XML, MimeType::APPLICATION_JSON])]
    public function httpException(): void
    {
        throw new HttpException(HttpExceptionDefinition::HTTP_BAD_REQUEST);
    }

    #[Route(path: '/upload', name: 'uploadTest', methods: [Request::METHOD_POST])]
    #[ItspireFrameworkExtra\FileParam('fParam')]
    public function upload(UploadedFile $fParam = null): Response
    {
        $content = 'File Infos :<br/>';
        $content .= '{{' . $fParam->getClientOriginalName() . '}}<br/>';
        $content .= '{{' . $fParam->getClientOriginalExtension() . '}}<br/>';
        $content .= '{{' . $fParam->getSize() . '}}<br/>';

        return new Response($content, HttpResponseStatus::HTTP_OK->value);
    }

    #[ItspireFrameworkExtra\Route(path: '/getFile', name: 'getFileTest', methods: [HttpMethod::GET])]
    public function getFile(): File
    {
        return new File(realpath(__DIR__ . '/../../../../resources/test.txt'));
    }

    #[ItspireFrameworkExtra\Route(path: '/securitySuccess', name: 'securitySuccessTest', methods: [HttpMethod::GET])]
    #[ItspireFrameworkExtra\Security(expression: 'true', responseStatus: HttpResponseStatus::HTTP_FORBIDDEN)]
    public function securitySuccess(): Response
    {
        return new Response('success', HttpResponseStatus::HTTP_OK->value);
    }

    #[ItspireFrameworkExtra\Route(path: '/securityFail', name: 'securityFailTest', methods: [HttpMethod::GET])]
    #[ItspireFrameworkExtra\Security(expression: 'false', responseStatus: HttpResponseStatus::HTTP_FORBIDDEN)]
    public function securityFail(): Response
    {
        return new Response('fail', HttpResponseStatus::HTTP_OK->value);
    }
}
