<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Unit\Util\Strategy\Annotation;

use Itspire\Common\Enum\Http\HttpResponseStatus;
use Itspire\Exception\Definition\Http\HttpExceptionDefinition;
use Itspire\Exception\Http\HttpException;
use Itspire\FrameworkExtraBundle\Annotation\FileParam;
use Itspire\FrameworkExtraBundle\Annotation\Route;
use Itspire\FrameworkExtraBundle\Tests\Unit\Fixtures\FixtureController;
use Itspire\FrameworkExtraBundle\Util\Strategy\Annotation\AnnotationHandler;
use Itspire\FrameworkExtraBundle\Util\Strategy\Annotation\AnnotationHandlerInterface;
use Itspire\FrameworkExtraBundle\Util\Strategy\Annotation\Processor\FileParamProcessor;
use Itspire\FrameworkExtraBundle\Util\Strategy\Annotation\Processor\RouteProcessor;
use Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck\TypeCheckHandlerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/** @deprecated */
class AnnotationHandlerTest extends TestCase
{
    private ?MockObject $loggerMock = null;
    private ?MockObject $typeCheckHandlerMock = null;
    private ?MockObject $fileParamProcessorMock = null;
    private ?MockObject $routeProcessorMock = null;
    private ?AnnotationHandlerInterface $annotationHandler = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->typeCheckHandlerMock = $this->getMockBuilder(TypeCheckHandlerInterface::class)->getMock();

        $this->fileParamProcessorMock = $this
            ->getMockBuilder(FileParamProcessor::class)
            ->setConstructorArgs([$this->typeCheckHandlerMock, $this->loggerMock])
            ->getMock();

        $this->routeProcessorMock = $this
            ->getMockBuilder(RouteProcessor::class)
            ->setConstructorArgs([$this->loggerMock])
            ->getMock();

        $this->annotationHandler = (new AnnotationHandler($this->loggerMock))
            ->registerProcessor($this->fileParamProcessorMock);
    }

    protected function tearDown(): void
    {
        unset($this->loggerMock, $this->typeCheckHandlerMock, $this->annotationHandler);

        parent::tearDown();
    }

    /** @test */
    public function registerProcessorTest(): void
    {
        $reflectionClass = new \ReflectionClass(AnnotationHandler::class);
        $reflectionProperty = $reflectionClass->getProperty('processors');
        $reflectionProperty->setAccessible(true);

        $reflectionPropertyPrioritized = $reflectionClass->getProperty('prioritizedProcessors');
        $reflectionPropertyPrioritized->setAccessible(true);

        static::assertCount(1, $reflectionProperty->getValue($this->annotationHandler));

        $this->annotationHandler->registerProcessor($this->fileParamProcessorMock);
        static::assertCount(1, $reflectionProperty->getValue($this->annotationHandler));

        $this->annotationHandler->registerProcessor($this->routeProcessorMock);
        static::assertCount(1, $reflectionProperty->getValue($this->annotationHandler));
        static::assertCount(1, $reflectionPropertyPrioritized->getValue($this->annotationHandler));
    }

    /** @test */
    public function processNoProcessorTest(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(HttpExceptionDefinition::HTTP_INTERNAL_SERVER_ERROR->value);
        $this->expectExceptionMessage(HttpExceptionDefinition::HTTP_INTERNAL_SERVER_ERROR->getDescription());

        $annotation = new Route(path: '/test', responseStatus: HttpResponseStatus::HTTP_OK);

        $this->loggerMock
            ->expects(static::once())
            ->method('error')
            ->with(
                sprintf(
                    'No processor found for annotation of class %s called in %s::process.',
                    $annotation::class,
                    AnnotationHandler::class
                )
            );

        (new AnnotationHandler($this->loggerMock))->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'fixture'],
                new Request(),
                HttpKernelInterface::MAIN_REQUEST
            ),
            $annotation
        );
    }

    /** @test */
    public function processRouteTest(): void
    {
        $this->annotationHandler->registerProcessor($this->routeProcessorMock);

        $event = new ControllerEvent(
            $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
            [new FixtureController(), 'fixture'],
            new Request(),
            HttpKernelInterface::MAIN_REQUEST
        );

        $annotation = new Route(path: '/test', responseStatus: HttpResponseStatus::HTTP_OK);

        $this->routeProcessorMock
            ->expects(static::once())
            ->method('supports')
            ->with($annotation)
            ->willReturn(true);

        $this->routeProcessorMock
            ->expects(static::once())
            ->method('process')
            ->with($event, $annotation);

        $this->annotationHandler->process($event, $annotation);
    }

    /** @test */
    public function processFileParamTest(): void
    {
        $this->annotationHandler->registerProcessor($this->routeProcessorMock);

        $file = new UploadedFile(realpath(__DIR__ . '/../../../../../resources/uploadedFile.txt'), 'uploadedFile.txt');
        $request = new Request(files: ['param' => $file]);

        $event = new ControllerEvent(
            $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
            [new FixtureController(), 'param'],
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $annotation = new FileParam(name: 'param');

        $this->routeProcessorMock
            ->expects(static::once())
            ->method('supports')
            ->with($annotation)
            ->willReturn(false);

        $this->fileParamProcessorMock
            ->expects(static::once())
            ->method('supports')
            ->with($annotation)
            ->willReturn(true);

        $this->fileParamProcessorMock
            ->expects(static::once())
            ->method('process')
            ->with($event, $annotation);

        $this->annotationHandler->process($event, $annotation);
    }
}
