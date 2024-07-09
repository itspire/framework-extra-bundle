<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Unit\Util\Strategy\Attribute;

use Itspire\Common\Enum\Http\HttpResponseStatus;
use Itspire\Exception\Definition\Http\HttpExceptionDefinition;
use Itspire\Exception\Http\HttpException;
use Itspire\FrameworkExtraBundle\Attribute as ItspireFrameworkExtra;
use Itspire\FrameworkExtraBundle\Tests\Unit\Fixtures\FixtureController;
use Itspire\FrameworkExtraBundle\Util\Strategy\Attribute\AttributeHandler;
use Itspire\FrameworkExtraBundle\Util\Strategy\Attribute\AttributeHandlerInterface;
use Itspire\FrameworkExtraBundle\Util\Strategy\Attribute\Processor\FileParamProcessor;
use Itspire\FrameworkExtraBundle\Util\Strategy\Attribute\Processor\RouteProcessor;
use Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck\TypeCheckHandlerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class AttributeHandlerTest extends TestCase
{
    private ?MockObject $loggerMock = null;
    private ?MockObject $typeCheckHandlerMock = null;
    private ?MockObject $fileParamProcessorMock = null;
    private ?MockObject $routeProcessorMock = null;
    private ?AttributeHandlerInterface $attributeHandler = null;

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

        $this->attributeHandler = (new AttributeHandler($this->loggerMock))
            ->registerProcessor($this->fileParamProcessorMock);
    }

    protected function tearDown(): void
    {
        unset($this->loggerMock, $this->typeCheckHandlerMock, $this->attributeHandler);

        parent::tearDown();
    }

    #[Test]
    public function registerProcessorTest(): void
    {
        $reflectionClass = new \ReflectionClass(AttributeHandler::class);

        $reflectionProperty = $reflectionClass->getProperty('processors');
        $reflectionProperty->setAccessible(true);

        $reflectionPropertyPrioritized = $reflectionClass->getProperty('prioritizedProcessors');
        $reflectionPropertyPrioritized->setAccessible(true);

        static::assertCount(expectedCount: 1, haystack: $reflectionProperty->getValue($this->attributeHandler));

        $this->attributeHandler->registerProcessor(attributeProcessor: $this->fileParamProcessorMock);
        static::assertCount(expectedCount: 1, haystack: $reflectionProperty->getValue($this->attributeHandler));

        $this->attributeHandler->registerProcessor(attributeProcessor: $this->routeProcessorMock);
        static::assertCount(expectedCount: 1, haystack: $reflectionProperty->getValue($this->attributeHandler));
        static::assertCount(
            expectedCount: 1,
            haystack: $reflectionPropertyPrioritized->getValue($this->attributeHandler)
        );
    }

    #[Test]
    public function processNoProcessorTest(): void
    {
        $exceptionDefinition = HttpExceptionDefinition::HTTP_INTERNAL_SERVER_ERROR;

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($exceptionDefinition->value);
        $this->expectExceptionMessage($exceptionDefinition->getDescription());

        $attribute = new ItspireFrameworkExtra\Route(path: '/test', responseStatus: HttpResponseStatus::HTTP_OK);

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with(
                vsprintf(
                    format: 'No processor found for attribute of class %s called in %s::process.',
                    values: [$attribute::class, AttributeHandler::class]
                )
            );

        (new AttributeHandler($this->loggerMock))->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'fixture'],
                new Request(),
                HttpKernelInterface::MAIN_REQUEST
            ),
            $attribute
        );
    }

    #[Test]
    public function processRouteTest(): void
    {
        $this->attributeHandler->registerProcessor($this->routeProcessorMock);

        $event = new ControllerEvent(
            $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
            [new FixtureController(), 'fixture'],
            new Request(),
            HttpKernelInterface::MAIN_REQUEST
        );

        $attribute = new ItspireFrameworkExtra\Route(path: '/test', responseStatus: HttpResponseStatus::HTTP_OK);

        $this->routeProcessorMock->expects($this->once())->method('supports')->with($attribute)->willReturn(true);
        $this->routeProcessorMock->expects($this->once())->method('process')->with($event, $attribute);

        $this->attributeHandler->process($event, $attribute);
    }

    #[Test]
    public function processFileParamTest(): void
    {
        $this->attributeHandler->registerProcessor($this->routeProcessorMock);

        $file = new UploadedFile(
            path: realpath(__DIR__ . '/../../../../../resources/uploadedFile.txt'),
            originalName: 'uploadedFile.txt'
        );
        $request = new Request(files: ['param' => $file]);

        $event = new ControllerEvent(
            $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
            [new FixtureController(), 'param'],
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $attribute = new ItspireFrameworkExtra\FileParam(name: 'param');

        $this->routeProcessorMock->expects($this->once())->method('supports')->with($attribute)->willReturn(false);

        $this->fileParamProcessorMock->expects($this->once())->method('supports')->with($attribute)->willReturn(true);

        $this->fileParamProcessorMock->expects($this->once())->method('process')->with($event, $attribute);

        $this->attributeHandler->process($event, $attribute);
    }
}
