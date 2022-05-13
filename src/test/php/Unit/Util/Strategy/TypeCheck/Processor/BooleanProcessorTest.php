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
use Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck\Processor\BooleanProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class BooleanProcessorTest extends TestCase
{
    private ?MockObject $loggerMock = null;
    private ?BooleanProcessor $booleanProcessor = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $this->booleanProcessor = new BooleanProcessor($this->loggerMock);
    }

    protected function tearDown(): void
    {
        unset($this->loggerMock, $this->booleanProcessor);

        parent::tearDown();
    }

    public function supportsProvider(): array
    {
        return [
            'notSupported' => ['string', false],
            'boolSupport' => ['bool', true],
            'booleanSupport' => ['boolean', true],
        ];
    }

    /**
     * @test
     * @dataProvider supportsProvider
     */
    public function supportsTest($type, $result): void
    {
        static::assertEquals(expected: $result, actual: $this->booleanProcessor->supports($type));
    }

    public function annotationOrAttributeProvider(): array
    {
        return [
            'paramAnnotation' => [new QueryParamAnnotation(name: 'param', type: 'bool')],
            'paramAttribute' => [new QueryParamAttribute(name: 'param', type: 'boolean')],
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
            ->with(
                'Invalid value type string provided for parameter param on route test : expected one of bool, boolean.'
            );

        $this->booleanProcessor->process($paramAttribute, $request, 'test');
    }

    /**
     * @test
     * @dataProvider annotationOrAttributeProvider
     */
    public function processTest(ParamAttributeInterface $paramAttribute): void
    {
        // truthy values
        foreach ([1, '1', true, 'true'] as $truthyValue) {
            static::assertEquals(
                expected: true,
                actual: $this->booleanProcessor->process(
                    paramAttribute: $paramAttribute,
                    request: new Request(),
                    value: $truthyValue
                )
            );
        }

        // falsy values
        foreach ([0, '0', false, 'false'] as $falsyValue) {
            static::assertEquals(
                expected: false,
                actual: $this->booleanProcessor->process(
                    paramAttribute: $paramAttribute,
                    request: new Request(),
                    value: $falsyValue
                )
            );
        }
    }
}
