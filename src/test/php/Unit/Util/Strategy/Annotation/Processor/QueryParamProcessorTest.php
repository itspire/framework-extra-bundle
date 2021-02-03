<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Unit\Util\Strategy\Annotation\Processor;

use Itspire\FrameworkExtraBundle\Annotation\Consumes;
use Itspire\FrameworkExtraBundle\Annotation\QueryParam;
use Itspire\FrameworkExtraBundle\Tests\Unit\Fixtures\FixtureController;
use Itspire\FrameworkExtraBundle\Util\Strategy\Annotation\Processor\QueryParamProcessor;
use Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck\TypeCheckHandlerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class QueryParamProcessorTest extends TestCase
{
    private ?MockObject $typeCheckHandlerMock = null;
    private ?QueryParamProcessor $queryParamProcessor = null;

    protected function setUp(): void
    {
        parent::setUp();

        $loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->typeCheckHandlerMock = $this->getMockBuilder(TypeCheckHandlerInterface::class)->getMock();

        $this->queryParamProcessor = new QueryParamProcessor($loggerMock, $this->typeCheckHandlerMock);
    }

    protected function tearDown(): void
    {
        unset($this->typeCheckHandlerMock, $this->queryParamProcessor);

        parent::tearDown();
    }

    public function supportsProvider(): array
    {
        return [
            'notSupported' => [new Consumes([]), false],
            'supported' => [new QueryParam([]), true],
        ];
    }

    /**
     * @test
     * @dataProvider supportsProvider
     */
    public function supportsTest($type, $result): void
    {
        static::assertEquals($result, $this->queryParamProcessor->supports($type));
    }

    /** @test */
    public function processTest(): void
    {
        $annotation = new QueryParam(
            ['name' => 'param', 'type' => 'int', 'required' => true, 'requirements' => '\d+']
        );
        $request = new Request(['param' => 1]);

        $this->typeCheckHandlerMock
            ->expects(static::once())
            ->method('process')
            ->with($annotation, $request, 1)
            ->willReturn(1);

        $this->queryParamProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'param'],
                $request,
                HttpKernelInterface::MASTER_REQUEST
            ),
            $annotation
        );

        static::assertEquals(1, $request->attributes->get('param'));
    }

    /** @test */
    public function processValueNotProvidedTest(): void
    {
        $annotation = new QueryParam(
            ['name' => 'param', 'type' => 'int', 'required' => false, 'requirements' => '\d+']
        );
        $request = new Request([]);

        $this->typeCheckHandlerMock
            ->expects(static::once())
            ->method('process')
            ->with($annotation, $request, null)
            ->willReturn(null);

        $this->queryParamProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'param'],
                $request,
                HttpKernelInterface::MASTER_REQUEST
            ),
            $annotation
        );

        static::assertNull($request->attributes->get('param'));
    }
}
