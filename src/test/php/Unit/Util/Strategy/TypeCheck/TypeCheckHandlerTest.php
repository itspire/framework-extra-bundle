<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Unit\Util\Strategy\TypeCheck;

use Itspire\Exception\Definition\Http\HttpExceptionDefinition;
use Itspire\Exception\Http\HttpException;
use Itspire\FrameworkExtraBundle\Annotation\QueryParam;
use Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck\Processor\IntegerProcessor;
use Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck\Processor\StringProcessor;
use Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck\TypeCheckHandler;
use Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck\TypeCheckHandlerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class TypeCheckHandlerTest extends TestCase
{
    private ?MockObject $loggerMock = null;
    private ?MockObject $integerProcessorMock = null;
    private ?MockObject $stringProcessorMock = null;
    private ?TypeCheckHandlerInterface $typeCheckHandler = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $this->integerProcessorMock = $this
            ->getMockBuilder(IntegerProcessor::class)
            ->setConstructorArgs([$this->loggerMock])
            ->getMock();

        $this->stringProcessorMock = $this
            ->getMockBuilder(StringProcessor::class)
            ->setConstructorArgs([$this->loggerMock])
            ->getMock();

        $this->typeCheckHandler = (new TypeCheckHandler($this->loggerMock))
            ->registerProcessor($this->integerProcessorMock);
    }

    protected function tearDown(): void
    {
        unset($this->loggerMock, $this->typeCheckHandlerMock, $this->typeCheckHandler);

        parent::tearDown();
    }

    /** @test */
    public function registerProcessorTest(): void
    {
        $reflectionClass = new \ReflectionClass(TypeCheckHandler::class);
        $reflectionProperty = $reflectionClass->getProperty('processors');
        $reflectionProperty->setAccessible(true);

        static::assertCount(1, $reflectionProperty->getValue($this->typeCheckHandler));

        $this->typeCheckHandler->registerProcessor($this->integerProcessorMock);
        static::assertCount(1, $reflectionProperty->getValue($this->typeCheckHandler));

        $this->typeCheckHandler->registerProcessor($this->stringProcessorMock);
        static::assertCount(2, $reflectionProperty->getValue($this->typeCheckHandler));
    }

    /** @test */
    public function processNoProcessorTest(): void
    {
        $exceptionDefinition = new HttpExceptionDefinition(HttpExceptionDefinition::HTTP_INTERNAL_SERVER_ERROR);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($exceptionDefinition->getValue());
        $this->expectExceptionMessage($exceptionDefinition->getDescription());

        $annotation = new QueryParam(['name' => 'param', 'type' => 'int']);

        $request = new Request();
        $request->attributes->set('_route', 'fixture');

        $this->loggerMock
            ->expects(static::once())
            ->method('error')
            ->with(
                sprintf(
                    'No processor found to check value of expected type %s in annotation %s on route %s.',
                    $annotation->getType(),
                    $annotation->getName(),
                    $request->attributes->get('_route')
                )
            );

        (new TypeCheckHandler($this->loggerMock))->process($annotation, $request, 1);
    }

    /** @test */
    public function processNoValidProcessorTest(): void
    {
        $exceptionDefinition = new HttpExceptionDefinition(HttpExceptionDefinition::HTTP_INTERNAL_SERVER_ERROR);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($exceptionDefinition->getValue());
        $this->expectExceptionMessage($exceptionDefinition->getDescription());

        $annotation = new QueryParam(['name' => 'param', 'type' => 'string']);

        $request = new Request();
        $request->attributes->set('_route', 'fixture');

        $this->integerProcessorMock
            ->expects(static::once())
            ->method('supports')
            ->with('string')
            ->willReturn(false);

        $this->loggerMock
            ->expects(static::once())
            ->method('error')
            ->with(
                sprintf(
                    'No processor found to check value of expected type %s in annotation %s on route %s.',
                    $annotation->getType(),
                    $annotation->getName(),
                    $request->attributes->get('_route')
                )
            );

        $this->typeCheckHandler->process($annotation, $request, 'aaa');
    }

    /** @test */
    public function processNoTypeValidationRequiredTest(): void
    {
        $annotation = new QueryParam(['name' => 'param']);

        $request = new Request();
        $request->attributes->set('_route', 'fixture');

        static::assertEquals(1, $this->typeCheckHandler->process($annotation, $request, 1));
    }

    /** @test */
    public function processTest(): void
    {
        $annotation = new QueryParam(['name' => 'param', 'type' => 'int']);

        $request = new Request();
        $request->attributes->set('_route', 'fixture');

        $this->integerProcessorMock
            ->expects(static::once())
            ->method('supports')
            ->with('int')
            ->willReturn(true);

        $this->integerProcessorMock
            ->expects(static::once())
            ->method('process')
            ->with($annotation, $request, 1)
            ->willReturn(1);

        static::assertEquals(1, $this->typeCheckHandler->process($annotation, $request, 1));
    }
}
