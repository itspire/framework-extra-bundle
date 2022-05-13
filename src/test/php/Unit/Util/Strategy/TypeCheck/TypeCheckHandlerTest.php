<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Unit\Util\Strategy\TypeCheck;

use Itspire\Exception\Definition\Http\HttpExceptionDefinition;
use Itspire\Exception\Http\HttpException;
use Itspire\FrameworkExtraBundle\Annotation\QueryParam as QueryParamAnnotation;
use Itspire\FrameworkExtraBundle\Attribute\ParamAttributeInterface;
use Itspire\FrameworkExtraBundle\Attribute\QueryParam as QueryParamAttribute;
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
        $reflectionProperty = $reflectionClass->getProperty(name: 'processors');
        $reflectionProperty->setAccessible(true);

        static::assertCount(
            expectedCount: 1,
            haystack: $reflectionProperty->getValue($this->typeCheckHandler)
        );

        $this->typeCheckHandler->registerProcessor($this->integerProcessorMock);
        static::assertCount(expectedCount: 1, haystack: $reflectionProperty->getValue($this->typeCheckHandler));

        $this->typeCheckHandler->registerProcessor($this->stringProcessorMock);
        static::assertCount(expectedCount: 2, haystack: $reflectionProperty->getValue($this->typeCheckHandler));
    }

    public function intAnnotationOrAttributeProvider(): array
    {
        return [
            'paramAnnotation' => [new QueryParamAnnotation(name: 'param', type: 'int')],
            'paramAttribute' => [new QueryParamAttribute(name: 'param', type: 'int')],
        ];
    }

    /**
     * @test
     * @dataProvider intAnnotationOrAttributeProvider
     */
    public function processNoProcessorTest(ParamAttributeInterface $paramAttribute): void
    {
        $exceptionDefinition = HttpExceptionDefinition::HTTP_INTERNAL_SERVER_ERROR;

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($exceptionDefinition->value);
        $this->expectExceptionMessage($exceptionDefinition->getDescription());

        $this->loggerMock
            ->expects(static::once())
            ->method('error')
            ->with('No processor found to check expected value type "int" for param "param" on route "test".');

        (new TypeCheckHandler($this->loggerMock))->process(
            paramAttribute: $paramAttribute,
            request: new Request(attributes: ['_route' => 'test']),
            value: 1
        );
    }

    public function stringAnnotationOrAttributeProvider(): array
    {
        return [
            'paramAnnotation' => [new QueryParamAnnotation(name: 'param', type: 'string')],
            'paramAttribute' => [new QueryParamAttribute(name: 'param', type: 'string')],
        ];
    }

    /**
     * @test
     * @dataProvider stringAnnotationOrAttributeProvider
     */
    public function processNoValidProcessorTest(ParamAttributeInterface $paramAttribute): void
    {
        $exceptionDefinition = HttpExceptionDefinition::HTTP_INTERNAL_SERVER_ERROR;

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($exceptionDefinition->value);
        $this->expectExceptionMessage($exceptionDefinition->getDescription());

        $this->integerProcessorMock
            ->expects(static::once())
            ->method('supports')
            ->with('string')
            ->willReturn(false);

        $this->loggerMock
            ->expects(static::once())
            ->method('error')
            ->with('No processor found to check expected value type "string" for param "param" on route "test".');

        $this->typeCheckHandler->process(
            paramAttribute: $paramAttribute,
            request: new Request(attributes: ['_route' => 'test']),
            value: 'aaa'
        );
    }

    public function noTypeAnnotationOrAttributeProvider(): array
    {
        return [
            'paramAnnotation' => [new QueryParamAnnotation(name: 'param')],
            'paramAttribute' => [new QueryParamAttribute(name: 'param')],
        ];
    }

    /**
     * @test
     * @dataProvider noTypeAnnotationOrAttributeProvider
     */
    public function processNoTypeValidationRequiredTest(ParamAttributeInterface $paramAttribute): void
    {
        static::assertEquals(
            expected: 1,
            actual: $this->typeCheckHandler->process(
                paramAttribute: $paramAttribute,
                request: new Request(attributes: ['_route' => 'test']),
                value: 1
            )
        );
    }

    /**
     * @test
     * @dataProvider intAnnotationOrAttributeProvider
     */
    public function processTest(ParamAttributeInterface $paramAttribute): void
    {
        $request = new Request(attributes: ['_route' => 'test']);

        $this->integerProcessorMock->expects(static::once())->method('supports')->with('int')->willReturn(true);

        $this->integerProcessorMock
            ->expects(static::once())
            ->method('process')
            ->with($paramAttribute, $request, 1)
            ->willReturn(1);

        static::assertEquals(
            expected: 1,
            actual: $this->typeCheckHandler->process(paramAttribute: $paramAttribute, request: $request, value: 1)
        );
    }
}
