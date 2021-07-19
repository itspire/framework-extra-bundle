<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Unit\EventListener;

use Doctrine\Common\Annotations\AnnotationReader;
use Itspire\Common\Enum\Http\HttpResponseStatus;
use Itspire\FrameworkExtraBundle\Annotation as ItspireFrameworkExtraAnnotation;
use Itspire\FrameworkExtraBundle\EventListener\ControllerListener;
use Itspire\FrameworkExtraBundle\Tests\Unit\Fixtures\FixtureController;
use Itspire\FrameworkExtraBundle\Util\Strategy\Annotation\AnnotationHandlerInterface;
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
    private ?ControllerEvent $event = null;
    private ?ControllerListener $controllerListener = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->annotationReaderMock = $this->getMockBuilder(AnnotationReader::class)->getMock();
        $this->annotationHandlerMock = $this->getMockBuilder(AnnotationHandlerInterface::class)->getMock();

        $kernelMock = $this->getMockBuilder(HttpKernelInterface::class)->getMock();

        $file = new UploadedFile(realpath(__DIR__ . '/../../../resources/uploadedFile.txt'), 'uploadedFile.txt');
        $request = new Request(['param' => 'test1'], ['pParam' => 10], [], [], ['fParam' => $file], [], 'body');

        $this->event = new ControllerEvent(
            $kernelMock,
            [new FixtureController(), 'fixture'],
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $this->controllerListener = new ControllerListener($this->annotationReaderMock, $this->annotationHandlerMock);
    }

    protected function tearDown(): void
    {
        unset($this->controllerListener, $this->annotationReaderMock, $this->annotationHandlerMock, $this->event);

        parent::tearDown();
    }

    /** @test */
    public function noAnnotationToProcessTest(): void
    {
        $this->event->getRequest()->headers->set('Accept', '*/*');

        $this->annotationReaderMock->expects(static::once())->method('getMethodAnnotations')->willReturn([]);
        $this->annotationHandlerMock->expects(static::never())->method('process');

        $this->controllerListener->onKernelController($this->event);
    }

    /** @test */
    public function oneAnnotationToProcessTest(): void
    {
        $this->event->getRequest()->headers->set('Accept', '*/*');

        $annotation = new ItspireFrameworkExtraAnnotation\Route(
            ['value' => '/test', 'responseStatus' => HttpResponseStatus::HTTP_OK]
        );

        $this->annotationReaderMock->expects(static::once())->method('getMethodAnnotations')->willReturn([$annotation]);
        $this->annotationHandlerMock
            ->expects(static::once())
            ->method('process')
            ->with($this->event, $annotation);

        $this->controllerListener->onKernelController($this->event);
    }
}
