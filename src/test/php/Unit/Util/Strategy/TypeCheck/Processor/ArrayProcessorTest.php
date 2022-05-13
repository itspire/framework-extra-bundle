<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Unit\Util\Strategy\TypeCheck\Processor;

use Itspire\Exception\Definition\Http\HttpExceptionDefinition;
use Itspire\Exception\Http\HttpException;
use Itspire\FrameworkExtraBundle\Annotation\QueryParam as QueryParamAnnotation;
use Itspire\FrameworkExtraBundle\Attribute\ParamAttributeInterface;
use Itspire\FrameworkExtraBundle\Attribute\QueryParam as QueryParamAttribute;
use Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck\Processor\ArrayProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class ArrayProcessorTest extends TestCase
{
    private ?MockObject $loggerMock = null;
    private ?ArrayProcessor $arrayProcessor = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $this->arrayProcessor = new ArrayProcessor($this->loggerMock);
    }

    protected function tearDown(): void
    {
        unset($this->loggerMock, $this->arrayProcessor);

        parent::tearDown();
    }

    public function supportsProvider(): array
    {
        return [
            'notSupported' => ['int', false],
            'supported' => ['array', true],
        ];
    }

    /**
     * @test
     * @dataProvider supportsProvider
     */
    public function supportsTest(string $type, bool $result): void
    {
        static::assertEquals(expected: $result, actual: $this->arrayProcessor->supports($type));
    }

    public function annotationOrAttributeProvider(): array
    {
        return [
            'paramAnnotation' => [new QueryParamAnnotation(name: 'param', type: 'array')],
            'paramAttribute' => [new QueryParamAttribute(name: 'param', type: 'array')],
        ];
    }

    /**
     * @test
     * @dataProvider annotationOrAttributeProvider
     */
    public function processUnsupportedTest(ParamAttributeInterface $paramAttribute): void
    {
        $exceptionDefinition = HttpExceptionDefinition::HTTP_BAD_REQUEST;

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($exceptionDefinition->value);
        $this->expectExceptionMessage($exceptionDefinition->getDescription());

        $request = new Request(attributes: ['_route' => 'test']);

        $this->loggerMock
            ->expects(static::once())
            ->method('alert')
            ->with('Invalid value type integer provided for parameter param on route test : expected one of array.');

        $this->arrayProcessor->process(paramAttribute: $paramAttribute, request: $request, value: 1);
    }

    /**
     * @test
     * @dataProvider annotationOrAttributeProvider
     */
    public function processTest(ParamAttributeInterface $paramAttribute): void
    {
        static::assertEquals(
            expected: [1, '111', 'aaa'],
            actual: $this->arrayProcessor->process($paramAttribute, new Request(), [1, '111', 'aaa'])
        );
    }
}
