<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Unit\Util\Strategy\Attribute\Processor;

use Itspire\Exception\Definition\Http\HttpExceptionDefinition;
use Itspire\Exception\Http\HttpException;
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

class ParamProcessorTest extends TestCase
{
    protected MockObject | LoggerInterface | null $loggerMock = null;
    protected MockObject | TypeCheckHandlerInterface | null $typeCheckHandlerMock = null;
    protected ?QueryParamProcessor $queryParamProcessor = null;

    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->typeCheckHandlerMock = $this->getMockBuilder(TypeCheckHandlerInterface::class)->getMock();

        $this->queryParamProcessor = new QueryParamProcessor($this->typeCheckHandlerMock, $this->loggerMock);
    }

    protected function tearDown(): void
    {
        unset($this->queryParamProcessor, $this->typeCheckHandlerMock, $this->loggerMock);
    }

    /** @test */
    public function processUnsupportedClassTest(): void
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
                    values: [$consumes::class, $this->queryParamProcessor::class]
                )
            );

        $this->queryParamProcessor->process(
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
    public function processSupportedClassWithParameterNameConflictTest(): void
    {
        $exceptionDefinition = HttpExceptionDefinition::HTTP_INTERNAL_SERVER_ERROR;

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($exceptionDefinition->value);
        $this->expectExceptionMessage($exceptionDefinition->getDescription());

        $queryParam = $this->getQueryParam();

        $request = new Request(attributes: ['param' => 'test']);

        $this->loggerMock
            ->expects(static::once())
            ->method('error')
            ->with(
                vsprintf(
                    format: 'Name conflict detected for parameter %s in route %s.',
                    values: [$queryParam->getName(), $request->attributes->get(key: '_route')]
                )
            );

        $this->queryParamProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'param'],
                $request,
                HttpKernelInterface::MAIN_REQUEST
            ),
            $queryParam
        );
    }

    /** @test */
    public function processSupportedClassWithParameterNotDefinedOnMethodTest(): void
    {
        $exceptionDefinition = HttpExceptionDefinition::HTTP_INTERNAL_SERVER_ERROR;

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($exceptionDefinition->value);
        $this->expectExceptionMessage($exceptionDefinition->getDescription());

        $queryParam = $this->getQueryParam();

        $reflectionMethod = new \ReflectionMethod(FixtureController::class, method: 'fixture');

        $this->loggerMock
            ->expects(static::once())
            ->method('error')
            ->with(
                vsprintf(
                    format: 'Parameter %s does not exist on method %s',
                    values: [
                        $queryParam->getName(),
                        $reflectionMethod->getDeclaringClass()->getName() . '::' . $reflectionMethod->getName()
                    ]
                )
            );

        $this->queryParamProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'fixture'],
                new Request(),
                HttpKernelInterface::MAIN_REQUEST
            ),
            $queryParam
        );
    }

    /** @test */
    public function processSupportedClassWithMissingRequiredParameterTest(): void
    {
        $exceptionDefinition = HttpExceptionDefinition::HTTP_BAD_REQUEST;

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($exceptionDefinition->value);
        $this->expectExceptionMessage($exceptionDefinition->getDescription());

        $queryParam = $this->getQueryParam();
        $request = new Request();

        $this->loggerMock
            ->expects(static::once())
            ->method('alert')
            ->with(
                vsprintf(
                    format: '"%s" defined on route "%s" has no matching "%s" parameter in the request.',
                    values: [$queryParam::class, $request->attributes->get(key: '_route'), $queryParam->getName()]
                )
            );

        $this->queryParamProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'param'],
                $request,
                HttpKernelInterface::MAIN_REQUEST
            ),
            $queryParam
        );
    }

    /** @test */
    public function processSupportedClassWithTypeCheckErrorTest(): void
    {
        $exceptionDefinition = HttpExceptionDefinition::HTTP_BAD_REQUEST;

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($exceptionDefinition->value);
        $this->expectExceptionMessage($exceptionDefinition->getDescription());

        $queryParam = $this->getQueryParam();
        $request = new Request(query: ['param' => 1]);

        $this->typeCheckHandlerMock
            ->expects(static::once())
            ->method('process')
            ->with($queryParam, $request, 1)
            ->willThrowException(new HttpException($exceptionDefinition));

        $this->queryParamProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'param'],
                $request,
                HttpKernelInterface::MAIN_REQUEST
            ),
            $queryParam
        );
    }

    /** @test */
    public function processSupportedClassWithOverriddenTypeCheckErrorTest(): void
    {
        $exceptionDefinition = HttpExceptionDefinition::HTTP_BAD_REQUEST;

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($exceptionDefinition->value);
        $this->expectExceptionMessage($exceptionDefinition->getDescription());

        $queryParam = $this->getQueryParam();
        $request = new Request(query: ['param' => '1']);

        $this->typeCheckHandlerMock
            ->expects(static::once())
            ->method('process')
            ->with($queryParam, $request, '1')
            ->willThrowException(new HttpException($exceptionDefinition));

        $this->queryParamProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'typedParam'],
                $request,
                HttpKernelInterface::MAIN_REQUEST
            ),
            $queryParam
        );
    }

    /** @test */
    public function processSupportedClassWithRequirementsErrorTest(): void
    {
        $exceptionDefinition = HttpExceptionDefinition::HTTP_BAD_REQUEST;

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($exceptionDefinition->value);
        $this->expectExceptionMessage($exceptionDefinition->getDescription());

        $queryParam = $this->getQueryParam(requirements: '\d{2,}');
        $request = new Request(query: ['param' => '1']);

        $this->typeCheckHandlerMock
            ->expects(static::once())
            ->method('process')
            ->with($queryParam, $request, '1')
            ->willReturn('1');

        $this->loggerMock
            ->expects(static::once())
            ->method('alert')
            ->with(
                vsprintf(
                    format: 'Parameter value for %s does not match defined requirement %s.',
                    values: [$queryParam->getName(), $queryParam->getRequirements()]
                )
            );

        $this->queryParamProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'param'],
                $request,
                HttpKernelInterface::MAIN_REQUEST
            ),
            $queryParam
        );
    }

    /** @test */
    public function processSupportedClassWithArrayRequirementsErrorTest(): void
    {
        $exceptionDefinition = HttpExceptionDefinition::HTTP_BAD_REQUEST;

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($exceptionDefinition->value);
        $this->expectExceptionMessage($exceptionDefinition->getDescription());

        $queryParam = $this->getQueryParam(type: 'array', requirements: '\d{2,}');
        $request = new Request(query: ['param' => [1]]);

        $this->typeCheckHandlerMock
            ->expects(static::once())
            ->method('process')
            ->with($queryParam, $request, [1])
            ->willReturn([1]);

        $this->loggerMock
            ->expects(static::once())
            ->method('alert')
            ->with(
                vsprintf(
                    format: 'Parameter value for %s does not match defined requirement %s.',
                    values: [$queryParam->getName(), $queryParam->getRequirements()]
                )
            );

        $this->queryParamProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'param'],
                $request,
                HttpKernelInterface::MAIN_REQUEST
            ),
            $queryParam
        );
    }

    protected function getConsumes(): Consumes
    {
        return new Consumes();
    }

    protected function getQueryParam(string $type = 'string', ?string $requirements = null): QueryParam
    {
        return new QueryParam(name: 'param', type: $type, required: true, requirements: $requirements);
    }
}
