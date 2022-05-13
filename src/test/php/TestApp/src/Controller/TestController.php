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
use Itspire\FrameworkExtraBundle\Annotation as ItspireFrameworkExtraAnno;
use Itspire\FrameworkExtraBundle\Attribute as ItspireFrameworkExtraAttr;
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
    /**
     * @Route("/index", name="indexTest", methods={Request::METHOD_POST})
     *
     * @ItspireFrameworkExtraAnno\Consumes({MimeType::APPLICATION_XML})
     * @ItspireFrameworkExtraAnno\Produces({MimeType::APPLICATION_JSON})
     *
     * @ItspireFrameworkExtraAnno\HeaderParam(name="contentType", headerName="Content-Type", type="string")
     * @ItspireFrameworkExtraAnno\QueryParam(name="qParam", type="string", requirements="\w+")
     * @ItspireFrameworkExtraAnno\RequestParam(name="rParam", type="int", requirements="\d+")
     * @ItspireFrameworkExtraAnno\BodyParam(name="bParam", type="class", class=TestObject::class)
     */
    public function index(
        $contentType = null,
        $qParam = null,
        $rParam = null,
        $bParam = null
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

    #[Route(path: '/indexAttr', name: 'indexAttrTest', methods: [Request::METHOD_POST])]
    #[ItspireFrameworkExtraAttr\Consumes([MimeType::APPLICATION_XML])]
    #[ItspireFrameworkExtraAttr\Produces([MimeType::APPLICATION_JSON])]
    #[ItspireFrameworkExtraAttr\HeaderParam(name: 'contentType', headerName: 'Content-Type', type: 'string')]
    #[ItspireFrameworkExtraAttr\QueryParam(name: 'qParam', requirements: '\w+')]
    #[ItspireFrameworkExtraAttr\RequestParam(name: 'rParam', requirements: '\d+')]
    #[ItspireFrameworkExtraAttr\BodyParam(name: 'bParam', class: TestObject::class)]
    public function indexAttr(
        ?string $contentType = null,
        ?string $qParam = null,
        ?int $rParam = null,
        ?TestObject $bParam = null
    ): Response {
        $result = [
            'Query Param Attr' => $qParam,
            'Request Param Attr' => $rParam,
            'Body Param Attr' => $bParam->getTestProperty(),
            'Header Param Attr' => $contentType,
        ];

        return new Response(
            json_encode($result),
            HttpResponseStatus::HTTP_OK->value,
            ['Content-Type' => MimeType::APPLICATION_JSON->value]
        );
    }

    /**
     * @ItspireFrameworkExtraAnno\Route("/serialize", name="serializeTest", methods={Request::METHOD_GET})
     * @ItspireFrameworkExtraAnno\Produces({MimeType::APPLICATION_XML})
     */
    public function serialize(): TestObject
    {
        $testObject = new TestObject();
        $testObject->setTestProperty('testing');

        return $testObject;
    }

    #[ItspireFrameworkExtraAttr\Route(
        path: '/serializeAttr',
        name: 'serializeAttrTest',
        methods: [Request::METHOD_GET]
    )]
    #[ItspireFrameworkExtraAttr\Produces([MimeType::APPLICATION_XML])]
    public function serializeAttr(): TestObject
    {
        $testObject = new TestObject();
        $testObject->setTestProperty('testingAttr');

        return $testObject;
    }

    /** @ItspireFrameworkExtraAnno\Route("/regular", name="regularTest", methods={Request::METHOD_GET}) */
    public function regular(): array
    {
        return ['testRegular'];
    }

    #[ItspireFrameworkExtraAttr\Route(path: '/regularAttr', name: 'regularAttrTest', methods: [HttpMethod::GET])]
    public function regularAttr(): array
    {
        return ['testRegularAttr'];
    }

    /**
     * @ItspireFrameworkExtraAnno\Route(
     *     "/regularWithTemplate",
     *     name="regularTemplateTest",
     *     methods={Request::METHOD_GET}
     * )
     * @Template("@ItspireFrameworkExtra/response.html.twig")
     */
    public function regularWithTemplate(): array
    {
        return ['controllerResult' => json_encode(['testWithTemplate']), 'format' => 'json'];
    }

    #[ItspireFrameworkExtraAttr\Route(
        path: '/regularWithTemplateAttr',
        name: 'regularTemplateAttrTest',
        methods: [HttpMethod::GET]
    )]
    #[Template('@ItspireFrameworkExtra/response.html.twig')]
    public function regularWithTemplateAttr(): array
    {
        return ['controllerResult' => json_encode(['testWithTemplateAttr']), 'format' => 'json'];
    }

    /**
     * @ItspireFrameworkExtraAnno\Route("/exception", name="exceptionTest", methods={Request::METHOD_GET})
     * @ItspireFrameworkExtraAnno\Produces({MimeType::APPLICATION_XML, MimeType::APPLICATION_JSON})
     */
    public function exception(): void
    {
        throw new WebserviceException(WebserviceExceptionDefinition::CONFLICT);
    }

    #[ItspireFrameworkExtraAttr\Route(path: '/exceptionAttr', name: 'exceptionAttrTest', methods: [HttpMethod::GET])]
    #[ItspireFrameworkExtraAttr\Produces([MimeType::APPLICATION_XML, MimeType::APPLICATION_JSON])]
    public function exceptionAttr(): void
    {
        throw new WebserviceException(WebserviceExceptionDefinition::RETRIEVAL);
    }

    /**
     * @ItspireFrameworkExtraAnno\Route("/httpException", name="httpExceptionTest", methods={Request::METHOD_GET})
     * @ItspireFrameworkExtraAnno\Produces({MimeType::APPLICATION_XML, MimeType::APPLICATION_JSON})
     */
    public function httpException(): void
    {
        throw new HttpException(HttpExceptionDefinition::HTTP_BAD_REQUEST);
    }

    #[ItspireFrameworkExtraAttr\Route(
        path: '/httpExceptionAttr',
        name: 'httpExceptionAttrTest',
        methods: [HttpMethod::GET]
    )]
    #[ItspireFrameworkExtraAttr\Produces([MimeType::APPLICATION_XML, MimeType::APPLICATION_JSON])]
    public function httpExceptionAttr(): void
    {
        throw new HttpException(HttpExceptionDefinition::HTTP_NOT_ACCEPTABLE);
    }

    /**
     * @Route("/upload", name="uploadTest", methods={Request::METHOD_POST})
     *
     * @ItspireFrameworkExtraAnno\FileParam(name="fParam")
     */
    public function upload(UploadedFile $fParam = null): Response
    {
        $content = 'File Infos :<br/>';
        $content .= '{{' . $fParam->getClientOriginalName() . '}}<br/>';
        $content .= '{{' . $fParam->getClientOriginalExtension() . '}}<br/>';
        $content .= '{{' . $fParam->getSize() . '}}<br/>';

        return new Response($content, HttpResponseStatus::HTTP_OK->value);
    }

    #[Route(path: '/uploadAttr', name: 'uploadAttrTest', methods: [Request::METHOD_POST])]
    #[ItspireFrameworkExtraAttr\FileParam('fParam')]
    public function uploadAttr(UploadedFile $fParam = null): Response
    {
        $content = 'File Infos :<br/>';
        $content .= '{{' . $fParam->getClientOriginalName() . '}}<br/>';
        $content .= '{{' . $fParam->getClientOriginalExtension() . '}}<br/>';
        $content .= '{{' . $fParam->getSize() . '}}<br/>';

        return new Response($content, HttpResponseStatus::HTTP_OK->value);
    }

    /** @ItspireFrameworkExtraAnno\Route("/getFile", name="getFileTest", methods={Request::METHOD_GET}) */
    public function getFile(): File
    {
        return new File(realpath(__DIR__ . '/../../../../resources/test.txt'));
    }

    #[ItspireFrameworkExtraAttr\Route(path: '/getFileAttr', name: 'getFileAttrTest', methods: [HttpMethod::GET])]
    public function getFileAttr(): File
    {
        return new File(realpath(__DIR__ . '/../../../../resources/test.txt'));
    }

    /**
     * @ItspireFrameworkExtraAnno\Route(
     *     "/securitySuccess",
     *     name="securitySuccessTest",
     *     methods={Request::METHOD_GET}
     * )
     * @ItspireFrameworkExtraAnno\Security(expression="true", responseStatus=HttpResponseStatus::HTTP_FORBIDDEN)
     */
    public function securitySuccess(): Response
    {
        return new Response('success', HttpResponseStatus::HTTP_OK->value);
    }

    #[ItspireFrameworkExtraAttr\Route(
        path: '/securitySuccessAttr',
        name: 'securitySuccessAttrTest',
        methods: [HttpMethod::GET]
    )]
    #[ItspireFrameworkExtraAttr\Security(expression: 'true', responseStatus: HttpResponseStatus::HTTP_FORBIDDEN)]
    public function securitySuccessAttr(): Response
    {
        return new Response('success', HttpResponseStatus::HTTP_OK->value);
    }

    /**
     * @ItspireFrameworkExtraAnno\Route("/securityFail", name="securityFailTest", methods={Request::METHOD_GET})
     * @ItspireFrameworkExtraAnno\Security(expression="false", responseStatus=HttpResponseStatus::HTTP_FORBIDDEN)
     */
    public function securityFail(): Response
    {
        return new Response('fail', HttpResponseStatus::HTTP_OK->value);
    }

    #[ItspireFrameworkExtraAttr\Route(
        path: '/securityFailAttr',
        name: 'securityFailAttrTest',
        methods: [HttpMethod::GET]
    )]
    #[ItspireFrameworkExtraAttr\Security(expression: 'false', responseStatus: HttpResponseStatus::HTTP_FORBIDDEN)]
    public function securityFailAttr(): Response
    {
        return new Response('fail', HttpResponseStatus::HTTP_OK->value);
    }
}
