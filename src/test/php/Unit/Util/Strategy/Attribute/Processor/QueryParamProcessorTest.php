<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Unit\Util\Strategy\Attribute\Processor;

use Itspire\FrameworkExtraBundle\Attribute\Consumes;
use Itspire\FrameworkExtraBundle\Attribute\QueryParam;
use Itspire\FrameworkExtraBundle\Tests\Unit\Fixtures\FixtureController;
use Itspire\FrameworkExtraBundle\Util\Strategy\Attribute\Processor\QueryParamProcessor;
use Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck\TypeCheckHandlerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class QueryParamProcessorTest extends TestCase
{
    protected MockObject | LoggerInterface | null $typeCheckHandlerMock = null;
    protected ?QueryParamProcessor $queryParamProcessor = null;

    protected function setUp(): void
    {
        $this->typeCheckHandlerMock = $this->getMockBuilder(TypeCheckHandlerInterface::class)->getMock();

        $this->queryParamProcessor = new QueryParamProcessor(
            $this->typeCheckHandlerMock,
            $this->getMockBuilder(LoggerInterface::class)->getMock()
        );
    }

    protected function tearDown(): void
    {
        unset($this->queryParamProcessor, $this->typeCheckHandlerMock);
    }

    public function supportsProvider(): array
    {
        return [
            'notSupported' => [new Consumes(), false],
            'supported' => [new QueryParam(name: 'param'), true],
        ];
    }

    /**
     * @test
     * @dataProvider supportsProvider
     */
    public function supportsTest($attribute, $result): void
    {
        static::assertEquals(expected: $result, actual: $this->queryParamProcessor->supports($attribute));
    }

    /** @test */
    public function processDefaultTest(): void
    {
        $queryParam = $this->getQueryParam(true, 10);
        $request = new Request();

        $this->typeCheckHandlerMock
            ->expects(static::once())
            ->method('process')
            ->with($queryParam, $request, 10)
            ->willReturn(10);

        $this->queryParamProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'param'],
                $request,
                HttpKernelInterface::MAIN_REQUEST
            ),
            $queryParam
        );

        static::assertEquals(expected: 10, actual: $request->attributes->get(key: 'param'));
    }

    /** @test */
    public function processTest(): void
    {
        $queryParam = $this->getQueryParam(true);
        $request = new Request(query: ['param' => 1]);

        $this->typeCheckHandlerMock
            ->expects(static::once())
            ->method('process')
            ->with($queryParam, $request, 1)
            ->willReturn(1);

        $this->queryParamProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'param'],
                $request,
                HttpKernelInterface::MAIN_REQUEST
            ),
            $queryParam
        );

        static::assertEquals(expected: 1, actual: $request->attributes->get(key: 'param'));
    }

    /** @test */
    public function processValueNotProvidedTest(): void
    {
        $queryParam = $this->getQueryParam(false);
        $request = new Request();

        $this->typeCheckHandlerMock
            ->expects(static::once())
            ->method('process')
            ->with($queryParam, $request, null)
            ->willReturn(null);

        $this->queryParamProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'param'],
                $request,
                HttpKernelInterface::MAIN_REQUEST
            ),
            $queryParam
        );

        static::assertNull(actual: $request->attributes->get(key: 'param'));
    }

    protected function getQueryParam(bool $required, mixed $default = null): QueryParam
    {
        return new QueryParam(name: 'param', type: 'int', required: $required, requirements: '\d+', default: $default);
    }
}
