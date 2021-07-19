<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\TestApp\Controller;

use Itspire\Common\Enum\Http\HttpResponseStatus;
use Itspire\Common\Enum\MimeType;
use Itspire\Exception\Definition\Webservice\WebserviceExceptionDefinition;
use Itspire\Exception\Webservice\WebserviceException;
use Itspire\FrameworkExtraBundle\Annotation as ItspireFrameworkExtraAnnotations;
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
     * @Route("/", name="indexTest", methods={Request::METHOD_POST})
     *
     * @ItspireFrameworkExtraAnnotations\Consumes({MimeType::APPLICATION_XML})
     * @ItspireFrameworkExtraAnnotations\Produces({MimeType::APPLICATION_JSON})
     *
     * @ItspireFrameworkExtraAnnotations\HeaderParam(name="contentType", headerName="Content-Type", type="string")
     * @ItspireFrameworkExtraAnnotations\QueryParam(name="qParam", type="string", requirements="\w+")
     * @ItspireFrameworkExtraAnnotations\RequestParam(name="rParam", type="int", requirements="\d+")
     * @ItspireFrameworkExtraAnnotations\BodyParam(name="bParam", type="class", class=TestObject::class)
     */
    public function index(
        $contentType = null,
        $qParam = null,
        $rParam = null,
        TestObject $bParam = null
    ): Response {
        $result = [
            'Query Param' => $qParam,
            'Request Param' => $rParam,
            'Body Param' => $bParam->getTestProperty(),
            'Header Param' => $contentType,
        ];

        return new Response(
            json_encode($result),
            HttpResponseStatus::HTTP_OK,
            ['Content-Type' => MimeType::APPLICATION_JSON]
        );
    }

    /**
     * @ItspireFrameworkExtraAnnotations\Route("/serialize", name="serializeTest", methods={Request::METHOD_GET})
     * @ItspireFrameworkExtraAnnotations\Produces({MimeType::APPLICATION_XML})
     */
    public function serialize(): TestObject
    {
        $testObject = new TestObject();
        $testObject->setTestProperty('testing');

        return $testObject;
    }

    /** @ItspireFrameworkExtraAnnotations\Route("/regular", name="regularTest", methods={Request::METHOD_GET}) */
    public function regular(): array
    {
        return ['test'];
    }

    /**
     * @ItspireFrameworkExtraAnnotations\Route("/regular2", name="regularTemplateTest", methods={Request::METHOD_GET})
     * @Template("@ItspireFrameworkExtra/response.html.twig")
     */
    public function regularWithTemplate(): array
    {
        return ['controllerResult' => json_encode(['test']), 'format' => 'json'];
    }

    /**
     * @ItspireFrameworkExtraAnnotations\Route("/exception", name="exceptionTest", methods={Request::METHOD_GET})
     * @ItspireFrameworkExtraAnnotations\Produces({MimeType::APPLICATION_XML, MimeType::APPLICATION_JSON})
     */
    public function exception(): void
    {
        throw new WebserviceException(
            new WebserviceExceptionDefinition(WebserviceExceptionDefinition::CONFLICT)
        );
    }

    /**
     * @Route("/upload", name="uploadTest", methods={Request::METHOD_POST})
     *
     * @ItspireFrameworkExtraAnnotations\FileParam(name="fParam")
     */
    public function upload(UploadedFile $fParam = null): Response
    {
        $content = 'File Infos :<br/>';
        $content .= '{{' . $fParam->getClientOriginalName() . '}}<br/>';
        $content .= '{{' . $fParam->getClientOriginalExtension() . '}}<br/>';
        $content .= '{{' . $fParam->getSize() . '}}<br/>';

        return new Response($content, HttpResponseStatus::HTTP_OK);
    }

    /** @ItspireFrameworkExtraAnnotations\Route("/get_file", name="getFileTest", methods={Request::METHOD_GET}) */
    public function getFile(): File
    {
        return new File(realpath(__DIR__ . '/../../../../resources/test.txt'));
    }

    /**
     * @ItspireFrameworkExtraAnnotations\Route(
     *     "/security_success",
     *     name="securitySuccessTest",
     *     methods={Request::METHOD_GET}
     * )
     * @ItspireFrameworkExtraAnnotations\Security(expression="true", responseStatus=HttpResponseStatus::HTTP_FORBIDDEN)
     */
    public function securitySuccess(): Response
    {
        return new Response('success', HttpResponseStatus::HTTP_OK);
    }

    /**
     * @ItspireFrameworkExtraAnnotations\Route("/security_fail", name="securityFailTest", methods={Request::METHOD_GET})
     * @ItspireFrameworkExtraAnnotations\Security(expression="false", responseStatus=HttpResponseStatus::HTTP_FORBIDDEN)
     */
    public function securityFail(): Response
    {
        return new Response('fail', HttpResponseStatus::HTTP_OK);
    }
}
