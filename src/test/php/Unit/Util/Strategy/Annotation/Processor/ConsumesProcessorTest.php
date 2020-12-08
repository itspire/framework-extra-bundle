<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Unit\Util\Strategy\Annotation\Processor;

use Itspire\Common\Enum\MimeType;
use Itspire\Exception\Http\Definition\HttpExceptionDefinition;
use Itspire\Exception\Http\HttpException;
use Itspire\FrameworkExtraBundle\Annotation\BodyParam;
use Itspire\FrameworkExtraBundle\Annotation\Consumes;
use Itspire\FrameworkExtraBundle\Configuration\CustomRequestAttributes;
use Itspire\FrameworkExtraBundle\Tests\Unit\Fixtures\FixtureController;
use Itspire\FrameworkExtraBundle\Util\MimeTypeMatcherInterface;
use Itspire\FrameworkExtraBundle\Util\Strategy\Annotation\Processor\ConsumesProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ConsumesProcessorTest extends TestCase
{
    private ?MockObject $loggerMock = null;
    private ?MockObject $mimeTypeMatcherMock = null;
    private ?ConsumesProcessor $consumesProcessor = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->mimeTypeMatcherMock = $this->getMockBuilder(MimeTypeMatcherInterface::class)->getMock();

        $this->consumesProcessor = new ConsumesProcessor($this->loggerMock, $this->mimeTypeMatcherMock);
    }

    protected function tearDown(): void
    {
        unset($this->loggerMock, $this->mimeTypeMatcherMock, $this->consumesProcessor);

        parent::tearDown();
    }

    public function supportsProvider(): array
    {
        return [
            'notSupported' => [new BodyParam([]), false],
            'supported' => [new Consumes([]), true],
        ];
    }

    /**
     * @test
     * @dataProvider supportsProvider
     */
    public function supportsTest($type, $result): void
    {
        static::assertEquals($result, $this->consumesProcessor->supports($type));
    }

    /** @test */
    public function processAlreadyProcessedTest(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(HttpExceptionDefinition::HTTP_INTERNAL_SERVER_ERROR[0]);
        $this->expectExceptionMessage(HttpExceptionDefinition::HTTP_INTERNAL_SERVER_ERROR[1]);

        $annotation = new Consumes(['value' => MimeType::APPLICATION_XML]);

        $request = new Request();
        $request->attributes->set(CustomRequestAttributes::CONSUMES_ANNOTATION_PROCESSED, true);

        $reflectionClass = new \ReflectionClass(FixtureController::class);
        $reflectionMethod = $reflectionClass->getMethod('param');

        $this->loggerMock
            ->expects(static::once())
            ->method('error')
            ->with(
                sprintf(
                    'Duplicate @Consumes annotation found on %s::%s.',
                    $reflectionClass->getName(),
                    $reflectionMethod->getName()
                )
            );

        $this->consumesProcessor->process(
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
    public function processUnsupportedMediaTypeTest(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(HttpExceptionDefinition::HTTP_UNSUPPORTED_MEDIA_TYPE[0]);
        $this->expectExceptionMessage(HttpExceptionDefinition::HTTP_UNSUPPORTED_MEDIA_TYPE[1]);

        $annotation = new Consumes(['value' => MimeType::APPLICATION_XML]);

        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => MimeType::TEXT_HTML]);

        $this->mimeTypeMatcherMock
            ->expects(static::once())
            ->method('findMimeTypeMatch')
            ->with([$request->headers->get('Content-Type')], $annotation->getConsumableContentTypes())
            ->willReturn(null);

        $this->loggerMock
            ->expects(static::once())
            ->method('alert')
            ->with(
                sprintf(
                    'Unsupported Media Type %s used for body content in route %s.',
                    $request->headers->get('Content-Type'),
                    $request->attributes->get('_route')
                )
            );

        $this->consumesProcessor->process(
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
    public function processTest(): void
    {
        $annotation = new Consumes(
            ['value' => MimeType::APPLICATION_XML, 'deserializationGroups' => ['Default', 'extended']]
        );

        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => MimeType::APPLICATION_XML]);

        $this->mimeTypeMatcherMock
            ->expects(static::once())
            ->method('findMimeTypeMatch')
            ->with([$request->headers->get('Content-Type')], $annotation->getConsumableContentTypes())
            ->willReturn(MimeType::APPLICATION_XML);

        $this->consumesProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'param'],
                $request,
                HttpKernelInterface::MASTER_REQUEST
            ),
            $annotation
        );

        static::assertTrue($request->attributes->get(CustomRequestAttributes::CONSUMES_ANNOTATION_PROCESSED));
        static::assertEquals(['Default', 'extended'], $annotation->getDeserializationGroups());
        static::assertEquals(
            ['Default', 'extended'],
            $request->attributes->get(CustomRequestAttributes::REQUEST_DESERIALIZATION_GROUPS)
        );
    }
}
