<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Unit\Util\Strategy\Attribute\Processor;

use Itspire\Common\Enum\Http\HttpResponseStatus;
use Itspire\Exception\Definition\Http\HttpExceptionDefinition;
use Itspire\Exception\Http\HttpException;
use Itspire\FrameworkExtraBundle\Attribute\Consumes;
use Itspire\FrameworkExtraBundle\Attribute\Route;
use Itspire\FrameworkExtraBundle\Configuration\CustomRequestAttributes;
use Itspire\FrameworkExtraBundle\Tests\Unit\Fixtures\FixtureController;
use Itspire\FrameworkExtraBundle\Util\Strategy\Attribute\Processor\RouteProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class RouteProcessorTest extends TestCase
{
    protected MockObject | LoggerInterface | null $loggerMock = null;
    protected ?RouteProcessor $routeProcessor = null;

    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $this->routeProcessor = new RouteProcessor($this->loggerMock);
    }

    protected function tearDown(): void
    {
        unset($this->routeProcessor, $this->loggerMock);
    }

    public function supportsProvider(): array
    {
        return [
            'notSupported' => [new Consumes([]), false],
            'supported' => [new Route(path: '/'), true],
        ];
    }

    /**
     * @test
     * @dataProvider supportsProvider
     */
    public function supportsTest($attribute, $result): void
    {
        static::assertEquals(expected: $result, actual: $this->routeProcessor->supports($attribute));
    }

    /** @test */
    public function processErrorTest(): void
    {
        $exceptionDefinition = HttpExceptionDefinition::HTTP_INTERNAL_SERVER_ERROR;

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($exceptionDefinition->value);
        $this->expectExceptionMessage($exceptionDefinition->getDescription());

        $consumes = $this->getConsumes();

        $this->loggerMock
            ->expects(static::once())
            ->method('error')
            ->with(
                vsprintf(
                    format: 'Unsupported class "%s" used in "%s::process".',
                    values: [$consumes::class, $this->routeProcessor::class]
                )
            );

        $this->routeProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'fixture'],
                new Request(),
                HttpKernelInterface::MAIN_REQUEST
            ),
            $consumes
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
                HttpKernelInterface::MAIN_REQUEST
            ),
            $this->getRoute()
        );

        static::assertTrue(condition: $request->attributes->get(key: CustomRequestAttributes::ROUTE_CALLED));
        static::assertEquals(
            expected: HttpResponseStatus::HTTP_OK->value,
            actual: $request->attributes->get(key: CustomRequestAttributes::RESPONSE_STATUS_CODE)
        );
    }

    protected function getConsumes(): Consumes
    {
        return new Consumes([]);
    }

    protected function getRoute(): Route
    {
        return new Route(path: '/test', responseStatus: HttpResponseStatus::HTTP_OK);
    }
}
