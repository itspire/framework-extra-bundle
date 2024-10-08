<?php

/*
 * Copyright (c) 2016 - 2024 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Unit\Util\Strategy\TypeCheck\Processor;

use Itspire\Exception\Definition\Http\HttpExceptionDefinition;
use Itspire\Exception\Http\HttpException;
use Itspire\FrameworkExtraBundle\Attribute\QueryParam;
use Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck\Processor\StringProcessor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class StringProcessorTest extends TestCase
{
    private MockObject | LoggerInterface | null $loggerMock = null;
    private ?StringProcessor $stringProcessor = null;

    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $this->stringProcessor = new StringProcessor($this->loggerMock);
    }

    protected function tearDown(): void
    {
        unset($this->stringProcessor, $this->loggerMock);
    }

    public static function supportsProvider(): array
    {
        return [
            'notSupported' => ['int', false],
            'supported' => ['string', true],
        ];
    }

    #[Test]
    #[DataProvider('supportsProvider')]
    public function supportsTest($type, $result): void
    {
        static::assertEquals(expected: $result, actual: $this->stringProcessor->supports($type));
    }

    #[Test]
    public function processUnsupportedTest(): void
    {
        $exceptionDefinition = HttpExceptionDefinition::HTTP_BAD_REQUEST;

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($exceptionDefinition->value);
        $this->expectExceptionMessage($exceptionDefinition->getDescription());

        $paramAttribute = new QueryParam(name: 'param', type: 'string');
        $request = new Request(attributes: ['_route' => 'test']);

        $this->loggerMock
            ->expects($this->once())
            ->method('alert')
            ->with('Invalid value type integer provided for parameter param on route test : expected one of string.');

        $this->stringProcessor->process(paramAttribute: $paramAttribute, request: $request, value: 1);
    }

    #[Test]
    public function processTest(): void
    {
        $paramAttribute = new QueryParam(name: 'param', type: 'string');

        static::assertEquals(
            expected: '111',
            actual: $this->stringProcessor->process(
                paramAttribute: $paramAttribute,
                request: new Request(),
                value: '111'
            )
        );
    }
}
