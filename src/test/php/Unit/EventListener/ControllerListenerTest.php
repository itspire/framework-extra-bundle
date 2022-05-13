<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Unit\EventListener;

use Doctrine\Common\Annotations\AnnotationReader;
use Itspire\Common\Enum\Http\HttpResponseStatus;
use Itspire\FrameworkExtraBundle\Annotation as ItspireFrameworkExtraAnnotation;
use Itspire\FrameworkExtraBundle\Attribute as ItspireFrameworkExtraAttribute;
use Itspire\FrameworkExtraBundle\EventListener\ControllerListener;
use Itspire\FrameworkExtraBundle\Tests\Unit\Fixtures\FixtureController;
use Itspire\FrameworkExtraBundle\Util\Strategy\Annotation\AnnotationHandlerInterface;
use Itspire\FrameworkExtraBundle\Util\Strategy\Attribute\AttributeHandlerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ControllerListenerTest extends TestCase
{
    private ?MockObject $annotationReaderMock = null;
    private ?MockObject $annotationHandlerMock = null;
    private ?MockObject $attributeHandlerMock = null;
    private ?Request $request = null;
    private ?ControllerListener $controllerListener = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->annotationReaderMock = $this->getMockBuilder(AnnotationReader::class)->getMock();
        $this->annotationHandlerMock = $this->getMockBuilder(AnnotationHandlerInterface::class)->getMock();
        $this->attributeHandlerMock = $this->getMockBuilder(AttributeHandlerInterface::class)->getMock();

        $file = new UploadedFile(realpath(__DIR__ . '/../../../resources/uploadedFile.txt'), 'uploadedFile.txt');
        $this->request = new Request(
            query: ['param' => 'test1'],
            request: ['pParam' => 10],
            files: ['fParam' => $file],
            content: 'body'
        );

        $this->controllerListener = new ControllerListener(
            $this->annotationReaderMock,
            $this->annotationHandlerMock,
            $this->attributeHandlerMock
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->controllerListener,
            $this->annotationReaderMock,
            $this->annotationHandlerMock,
            $this->attributeHandlerMock
        );

        parent::tearDown();
    }

    /** @test */
    public function noAnnotationAndNoAttibuteToProcessTest(): void
    {
        $this->request->headers->set(key: 'Accept', values: '*/*');

        $this->attributeHandlerMock->expects(static::never())->method('process');
        $this->annotationReaderMock->expects(static::once())->method('getMethodAnnotations')->willReturn([]);
        $this->annotationHandlerMock->expects(static::never())->method('process');

        $this->controllerListener->onKernelController(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'fixture'],
                $this->request,
                HttpKernelInterface::MAIN_REQUEST
            )
        );
    }

    /** @test */
    public function oneAnnotationToProcessTest(): void
    {
        $this->request->headers->set(key: 'Accept', values: '*/*');

        $annotation = new ItspireFrameworkExtraAnnotation\Route(
            path: '/test',
            responseStatus: HttpResponseStatus::HTTP_OK
        );

        $event = new ControllerEvent(
            $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
            [new FixtureController(), 'fixture'],
            $this->request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->attributeHandlerMock->expects(static::never())->method('process');
        $this->annotationReaderMock->expects(static::once())->method('getMethodAnnotations')->willReturn([$annotation]);
        $this->annotationHandlerMock->expects(static::once())->method('process')->with($event, $annotation);

        $this->controllerListener->onKernelController($event);
    }

    /** @test */
    public function oneAttributeToProcessTest(): void
    {
        $this->request->headers->set(key: 'Accept', values: '*/*');

        $annotation = new ItspireFrameworkExtraAttribute\Route(
            path: '/test',
            responseStatus: HttpResponseStatus::HTTP_OK
        );

        $event = new ControllerEvent(
            $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
            [new FixtureController(), 'fixtureWithAttribute'],
            $this->request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->attributeHandlerMock->expects(static::once())->method('process')->with($event, $annotation);
        $this->annotationReaderMock->expects(static::once())->method('getMethodAnnotations')->willReturn([]);
        $this->annotationHandlerMock->expects(static::never())->method('process');

        $this->controllerListener->onKernelController($event);
    }
}
