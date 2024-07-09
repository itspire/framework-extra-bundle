<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Unit\Util\Strategy\Attribute\Processor;

use Itspire\Common\Enum\MimeType;
use Itspire\FrameworkExtraBundle\Attribute\Consumes;
use Itspire\FrameworkExtraBundle\Attribute\HeaderParam;
use Itspire\FrameworkExtraBundle\Tests\Unit\Fixtures\FixtureController;
use Itspire\FrameworkExtraBundle\Util\Strategy\Attribute\Processor\HeaderParamProcessor;
use Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck\TypeCheckHandlerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class HeaderParamProcessorTest extends TestCase
{
    protected MockObject | TypeCheckHandlerInterface | null $typeCheckHandlerMock = null;
    protected ?HeaderParamProcessor $headerParamProcessor = null;

    protected function setUp(): void
    {
        $this->typeCheckHandlerMock = $this->getMockBuilder(TypeCheckHandlerInterface::class)->getMock();

        $this->headerParamProcessor = new HeaderParamProcessor(
            $this->typeCheckHandlerMock,
            $this->getMockBuilder(LoggerInterface::class)->getMock()
        );
    }

    protected function tearDown(): void
    {
        unset($this->typeCheckHandlerMock, $this->headerParamProcessor);
    }

    public static function supportsProvider(): array
    {
        return [
            'notSupported' => [new Consumes(), false],
            'supported' => [new HeaderParam(name: 'param'), true],
        ];
    }

    #[Test]
    #[DataProvider('supportsProvider')]
    public function supportsTest($attribute, $result): void
    {
        static::assertEquals(expected: $result, actual: $this->headerParamProcessor->supports($attribute));
    }

    #[Test]
    public function processDefaultTest(): void
    {
        $headerParam = $this->getHeaderParam(MimeType::APPLICATION_JSON->value);
        $request = new Request();

        $this->typeCheckHandlerMock
            ->expects($this->once())
            ->method('process')
            ->with($headerParam, $request, MimeType::APPLICATION_JSON->value)
            ->willReturn(MimeType::APPLICATION_JSON->value);

        $this->headerParamProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'param'],
                $request,
                HttpKernelInterface::MAIN_REQUEST
            ),
            $headerParam
        );

        static::assertEquals(
            expected: MimeType::APPLICATION_JSON->value,
            actual: $request->attributes->get(key: 'param')
        );
    }

    #[Test]
    public function processTest(): void
    {
        $headerParam = $this->getHeaderParam();
        $request = new Request(server: ['CONTENT_TYPE' => MimeType::APPLICATION_XML->value]);

        $this->typeCheckHandlerMock
            ->expects($this->once())
            ->method('process')
            ->with($headerParam, $request, MimeType::APPLICATION_XML->value)
            ->willReturn(MimeType::APPLICATION_XML->value);

        $this->headerParamProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'param'],
                $request,
                HttpKernelInterface::MAIN_REQUEST
            ),
            $headerParam
        );

        static::assertEquals(
            expected: MimeType::APPLICATION_XML->value,
            actual: $request->attributes->get(key: 'param')
        );
    }

    protected function getHeaderParam(mixed $default = null): HeaderParam
    {
        return new HeaderParam(name: 'param', type: 'string', headerName: 'Content-Type', default: $default);
    }
}
