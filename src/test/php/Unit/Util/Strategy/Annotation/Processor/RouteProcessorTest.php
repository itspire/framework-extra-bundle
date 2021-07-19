<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Unit\Util\Strategy\Annotation\Processor;

use Itspire\Common\Enum\Http\HttpResponseStatus;
use Itspire\Exception\Definition\Http\HttpExceptionDefinition;
use Itspire\Exception\Http\HttpException;
use Itspire\FrameworkExtraBundle\Annotation\Consumes;
use Itspire\FrameworkExtraBundle\Annotation\Route;
use Itspire\FrameworkExtraBundle\Configuration\CustomRequestAttributes;
use Itspire\FrameworkExtraBundle\Tests\Unit\Fixtures\FixtureController;
use Itspire\FrameworkExtraBundle\Util\Strategy\Annotation\Processor\RouteProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class RouteProcessorTest extends TestCase
{
    private ?MockObject $loggerMock = null;
    private ?RouteProcessor $routeProcessor = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $this->routeProcessor = new RouteProcessor($this->loggerMock);
    }

    protected function tearDown(): void
    {
        unset($this->loggerMock, $this->routeProcessor);

        parent::tearDown();
    }

    public function supportsProvider(): array
    {
        return [
            'notSupported' => [new Consumes([]), false],
            'supported' => [new Route([]), true],
        ];
    }

    /**
     * @test
     * @dataProvider supportsProvider
     */
    public function supportsTest($type, $result): void
    {
        static::assertEquals($result, $this->routeProcessor->supports($type));
    }

    /** @test */
    public function processErrorTest(): void
    {
        $exceptionDefinition = new HttpExceptionDefinition(HttpExceptionDefinition::HTTP_INTERNAL_SERVER_ERROR);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($exceptionDefinition->getValue());
        $this->expectExceptionMessage($exceptionDefinition->getDescription());

        $this->loggerMock
            ->expects(static::once())
            ->method('error')
            ->with(
                sprintf('Unsupported Annotation %s called %s::process.', Consumes::class, RouteProcessor::class)
            );

        $this->routeProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'fixture'],
                new Request(),
                HttpKernelInterface::MASTER_REQUEST
            ),
            new Consumes([])
        );
    }

    /** @test */
    public function processTest(): void
    {
        $request = new Request();

        $this->routeProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'fixture'],
                $request,
                HttpKernelInterface::MASTER_REQUEST
            ),
            new Route(['value' => '/test', 'responseStatus' => HttpResponseStatus::HTTP_OK])
        );

        static::assertTrue($request->attributes->get(CustomRequestAttributes::ROUTE_CALLED));
        static::assertEquals(
            HttpResponseStatus::HTTP_OK,
            $request->attributes->get(CustomRequestAttributes::RESPONSE_STATUS_CODE)
        );
    }
}
