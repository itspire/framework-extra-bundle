<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Unit\Util\Strategy\Annotation\Processor;

use Itspire\Exception\Definition\Http\HttpExceptionDefinition;
use Itspire\Exception\Http\HttpException;
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

class ParamProcessorTest extends TestCase
{
    private ?MockObject $loggerMock = null;
    private ?MockObject $typeCheckHandlerMock = null;
    private ?QueryParamProcessor $queryParamProcessor = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->typeCheckHandlerMock = $this->getMockBuilder(TypeCheckHandlerInterface::class)->getMock();

        $this->queryParamProcessor = new QueryParamProcessor($this->loggerMock, $this->typeCheckHandlerMock);
    }

    protected function tearDown(): void
    {
        unset($this->loggerMock, $this->typeCheckHandlerMock, $this->queryParamProcessor);

        parent::tearDown();
    }

    /** @test */
    public function processUnsupportedClassTest(): void
    {
        $exceptionDefinition = new HttpExceptionDefinition(HttpExceptionDefinition::HTTP_INTERNAL_SERVER_ERROR);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($exceptionDefinition->getValue());
        $this->expectExceptionMessage($exceptionDefinition->getDescription());

        $this->loggerMock
            ->expects(static::once())
            ->method('error')
            ->with(
                sprintf('Unsupported Annotation %s called %s::process.', Consumes::class, QueryParamProcessor::class)
            );

        $this->queryParamProcessor->process(
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
    public function processSupportedClassWithParameterNameConflictTest(): void
    {
        $exceptionDefinition = new HttpExceptionDefinition(HttpExceptionDefinition::HTTP_INTERNAL_SERVER_ERROR);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($exceptionDefinition->getValue());
        $this->expectExceptionMessage($exceptionDefinition->getDescription());

        $annotation = new QueryParam(['name' => 'param', 'type' => 'string', 'required' => true]);

        $request = new Request();
        $request->attributes->set('param', 'test');

        $event = new ControllerEvent(
            $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
            [new FixtureController(), 'param'],
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $this->loggerMock
            ->expects(static::once())
            ->method('error')
            ->with(
                sprintf(
                    'Name conflict detected for parameter %s in route %s.',
                    $annotation->getName(),
                    $request->attributes->get('_route')
                )
            );

        $this->queryParamProcessor->process($event, $annotation);
    }

    /** @test */
    public function processSupportedClassWithParameterNotDefinedOnMethodTest(): void
    {
        $exceptionDefinition = new HttpExceptionDefinition(HttpExceptionDefinition::HTTP_INTERNAL_SERVER_ERROR);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($exceptionDefinition->getValue());
        $this->expectExceptionMessage($exceptionDefinition->getDescription());

        $annotation = new QueryParam(['name' => 'param', 'type' => 'string', 'required' => true]);

        $reflectionClass = new \ReflectionClass(FixtureController::class);
        $reflectionMethod = $reflectionClass->getMethod('fixture');

        $this->loggerMock
            ->expects(static::once())
            ->method('error')
            ->with(
                sprintf(
                    'Parameter %s does not exist on method %s',
                    $annotation->getName(),
                    $reflectionClass->getName() . '::' . $reflectionMethod->getName()
                )
            );

        $this->queryParamProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'fixture'],
                new Request(),
                HttpKernelInterface::MASTER_REQUEST
            ),
            $annotation
        );
    }

    /** @test */
    public function processSupportedClassWithMissingRequiredParameterTest(): void
    {
        $exceptionDefinition = new HttpExceptionDefinition(HttpExceptionDefinition::HTTP_BAD_REQUEST);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($exceptionDefinition->getValue());
        $this->expectExceptionMessage($exceptionDefinition->getDescription());

        $annotation = new QueryParam(['name' => 'param', 'type' => 'string', 'required' => true]);
        $request = new Request();

        $this->loggerMock
            ->expects(static::once())
            ->method('alert')
            ->with(
                sprintf(
                    '@QueryParam annotation is defined on route %s but the corresponding value was not in the request.',
                    $request->attributes->get('_route')
                )
            );

        $this->queryParamProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'param'],
                $request,
                HttpKernelInterface::MASTER_REQUEST
            ),
            $annotation
        );
    }

    /** @test */
    public function processSupportedClassWithTypeCheckErrorTest(): void
    {
        $exceptionDefinition = new HttpExceptionDefinition(HttpExceptionDefinition::HTTP_BAD_REQUEST);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($exceptionDefinition->getValue());
        $this->expectExceptionMessage($exceptionDefinition->getDescription());

        $annotation = new QueryParam(['name' => 'param', 'type' => 'string', 'required' => true]);
        $request = new Request(['param' => 1]);

        $this->typeCheckHandlerMock
            ->expects(static::once())
            ->method('process')
            ->with($annotation, $request, 1)
            ->willThrowException(
                new HttpException(
                    new HttpExceptionDefinition(HttpExceptionDefinition::HTTP_BAD_REQUEST)
                )
            );

        $this->queryParamProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'param'],
                $request,
                HttpKernelInterface::MASTER_REQUEST
            ),
            $annotation
        );
    }

    /** @test */
    public function processSupportedClassWithOverriddenTypeCheckErrorTest(): void
    {
        $exceptionDefinition = new HttpExceptionDefinition(HttpExceptionDefinition::HTTP_BAD_REQUEST);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($exceptionDefinition->getValue());
        $this->expectExceptionMessage($exceptionDefinition->getDescription());

        $annotation = new QueryParam(['name' => 'param', 'type' => 'string', 'required' => true]);
        $request = new Request(['param' => '1']);

        $this->typeCheckHandlerMock
            ->expects(static::once())
            ->method('process')
            ->with($annotation, $request, '1')
            ->willThrowException(
                new HttpException(
                    new HttpExceptionDefinition(HttpExceptionDefinition::HTTP_BAD_REQUEST)
                )
            );

        $this->queryParamProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'typedParam'],
                $request,
                HttpKernelInterface::MASTER_REQUEST
            ),
            $annotation
        );
    }

    /** @test */
    public function processSupportedClassWithRequirementsErrorTest(): void
    {
        $exceptionDefinition = new HttpExceptionDefinition(HttpExceptionDefinition::HTTP_BAD_REQUEST);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($exceptionDefinition->getValue());
        $this->expectExceptionMessage($exceptionDefinition->getDescription());

        $annotation = new QueryParam(
            ['name' => 'param', 'type' => 'string', 'required' => true, 'requirements' => '\d{2,}']
        );
        $request = new Request(['param' => '1']);

        $this->typeCheckHandlerMock
            ->expects(static::once())
            ->method('process')
            ->with($annotation, $request, '1')
            ->willReturn('1');

        $this->loggerMock
            ->expects(static::once())
            ->method('alert')
            ->with(
                sprintf(
                    'Parameter value for %s does not match defined requirement %s.',
                    $annotation->getName(),
                    $annotation->getRequirements()
                )
            );

        $this->queryParamProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'param'],
                $request,
                HttpKernelInterface::MASTER_REQUEST
            ),
            $annotation
        );
    }

    /** @test */
    public function processSupportedClassWithArrayRequirementsErrorTest(): void
    {
        $exceptionDefinition = new HttpExceptionDefinition(HttpExceptionDefinition::HTTP_BAD_REQUEST);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($exceptionDefinition->getValue());
        $this->expectExceptionMessage($exceptionDefinition->getDescription());

        $annotation = new QueryParam(
            ['name' => 'param', 'type' => 'array', 'required' => true, 'requirements' => '\d{2,}']
        );
        $request = new Request(['param' => [1]]);

        $this->typeCheckHandlerMock
            ->expects(static::once())
            ->method('process')
            ->with($annotation, $request, [1])
            ->willReturn([1]);

        $this->loggerMock
            ->expects(static::once())
            ->method('alert')
            ->with(
                sprintf(
                    'Parameter value for %s does not match defined requirement %s.',
                    $annotation->getName(),
                    $annotation->getRequirements()
                )
            );

        $this->queryParamProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'param'],
                $request,
                HttpKernelInterface::MASTER_REQUEST
            ),
            $annotation
        );
    }
}
