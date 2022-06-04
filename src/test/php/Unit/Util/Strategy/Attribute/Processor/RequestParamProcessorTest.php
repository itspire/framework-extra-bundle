<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Unit\Util\Strategy\Attribute\Processor;

use Itspire\FrameworkExtraBundle\Attribute\Consumes;
use Itspire\FrameworkExtraBundle\Attribute\RequestParam;
use Itspire\FrameworkExtraBundle\Tests\Unit\Fixtures\FixtureController;
use Itspire\FrameworkExtraBundle\Util\Strategy\Attribute\Processor\RequestParamProcessor;
use Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck\TypeCheckHandlerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class RequestParamProcessorTest extends TestCase
{
    protected MockObject | TypeCheckHandlerInterface | null $typeCheckHandlerMock = null;
    protected ?RequestParamProcessor $requestParamProcessor = null;

    protected function setUp(): void
    {
        $this->typeCheckHandlerMock = $this->getMockBuilder(TypeCheckHandlerInterface::class)->getMock();

        $this->requestParamProcessor = new RequestParamProcessor(
            $this->typeCheckHandlerMock,
            $this->getMockBuilder(LoggerInterface::class)->getMock()
        );
    }

    protected function tearDown(): void
    {
        unset($this->requestParamProcessor, $this->typeCheckHandlerMock);
    }

    public function supportsProvider(): array
    {
        return [
            'notSupported' => [new Consumes([]), false],
            'supported' => [new RequestParam(name: 'param'), true],
        ];
    }

    /**
     * @test
     * @dataProvider supportsProvider
     */
    public function supportsTest($attribute, $result): void
    {
        static::assertEquals(expected: $result, actual: $this->requestParamProcessor->supports($attribute));
    }

    /** @test */
    public function processDefaultTest(): void
    {
        $requestParam = $this->getRequestParam(10);
        $request = new Request();

        $this->typeCheckHandlerMock
            ->expects(static::once())
            ->method('process')
            ->with($requestParam, $request, 10)
            ->willReturn(10);

        $this->requestParamProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'param'],
                $request,
                HttpKernelInterface::MAIN_REQUEST
            ),
            $requestParam
        );

        static::assertEquals(expected: 10, actual: $request->attributes->get(key: 'param'));
    }

    /** @test */
    public function processTest(): void
    {
        $requestParam = $this->getRequestParam();
        $request = new Request(request: ['param' => 1]);

        $this->typeCheckHandlerMock
            ->expects(static::once())
            ->method('process')
            ->with($requestParam, $request, 1)
            ->willReturn(1);

        $this->requestParamProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'param'],
                $request,
                HttpKernelInterface::MAIN_REQUEST
            ),
            $requestParam
        );

        static::assertEquals(expected: 1, actual: $request->attributes->get(key: 'param'));
    }

    protected function getRequestParam(mixed $default = null): RequestParam
    {
        return new RequestParam(name: 'param', type: 'int', required: true, requirements: '\d+', default: $default);
    }
}
