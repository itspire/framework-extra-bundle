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
use Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck\Processor\ScalarProcessor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class ScalarProcessorTest extends TestCase
{
    private MockObject | LoggerInterface | null $loggerMock = null;
    private ?ScalarProcessor $scalarProcessor = null;

    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $this->scalarProcessor = new ScalarProcessor($this->loggerMock);
    }

    protected function tearDown(): void
    {
        unset($this->scalarProcessor, $this->loggerMock);
    }

    public static function supportsProvider(): array
    {
        return [
            'notSupported' => ['int', false],
            'supported' => ['scalar', true],
        ];
    }

    #[Test]
    #[DataProvider('supportsProvider')]
    public function supportsTest($type, $result): void
    {
        static::assertEquals(expected: $result, actual: $this->scalarProcessor->supports($type));
    }

    #[Test]
    public function processUnsupportedTest(): void
    {
        $exceptionDefinition = HttpExceptionDefinition::HTTP_BAD_REQUEST;

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($exceptionDefinition->value);
        $this->expectExceptionMessage($exceptionDefinition->getDescription());

        $paramAttribute = new QueryParam(name: 'param', type: 'scalar');
        $request = new Request(attributes: ['_route' => 'test']);

        $this->loggerMock
            ->expects($this->once())
            ->method('alert')
            ->with('Invalid value type array provided for parameter param on route test : expected one of scalar.');

        $this->scalarProcessor->process(paramAttribute: $paramAttribute, request: $request, value: []);
    }

    #[Test]
    public function processTest(): void
    {
        $paramAttribute = new QueryParam(name: 'param', type: 'scalar');

        static::assertEquals(
            expected: '111',
            actual: $this->scalarProcessor->process($paramAttribute, new Request(), '111')
        );
    }
}
