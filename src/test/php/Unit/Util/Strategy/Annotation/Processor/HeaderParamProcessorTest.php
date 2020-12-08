<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Unit\Util\Strategy\Annotation\Processor;

use Itspire\Common\Enum\MimeType;
use Itspire\FrameworkExtraBundle\Annotation\Consumes;
use Itspire\FrameworkExtraBundle\Annotation\HeaderParam;
use Itspire\FrameworkExtraBundle\Tests\Unit\Fixtures\FixtureController;
use Itspire\FrameworkExtraBundle\Util\Strategy\Annotation\Processor\HeaderParamProcessor;
use Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck\TypeCheckHandlerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class HeaderParamProcessorTest extends TestCase
{
    private ?MockObject $typeCheckHandlerMock = null;
    private ?HeaderParamProcessor $headerParamProcessor = null;

    protected function setUp(): void
    {
        parent::setUp();

        $loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->typeCheckHandlerMock = $this->getMockBuilder(TypeCheckHandlerInterface::class)->getMock();

        $this->headerParamProcessor = new HeaderParamProcessor($loggerMock, $this->typeCheckHandlerMock);
    }

    protected function tearDown(): void
    {
        unset($this->typeCheckHandlerMock, $this->headerParamProcessor);

        parent::tearDown();
    }

    public function supportsProvider(): array
    {
        return [
            'notSupported' => [new Consumes([]), false],
            'supported' => [new HeaderParam([]), true],
        ];
    }

    /**
     * @test
     * @dataProvider supportsProvider
     */
    public function supportsTest($type, $result): void
    {
        static::assertEquals($result, $this->headerParamProcessor->supports($type));
    }

    /** @test */
    public function processTest(): void
    {
        $annotation = new HeaderParam(['name' => 'param', 'headerName' => 'content-type', 'type' => 'string']);
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => MimeType::APPLICATION_XML]);

        $this->typeCheckHandlerMock
            ->expects(static::once())
            ->method('process')
            ->with($annotation, $request, MimeType::APPLICATION_XML)
            ->willReturn(MimeType::APPLICATION_XML);

        $this->headerParamProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'param'],
                $request,
                HttpKernelInterface::MASTER_REQUEST
            ),
            $annotation
        );

        static::assertEquals(MimeType::APPLICATION_XML, $request->attributes->get('param'));
    }
}
