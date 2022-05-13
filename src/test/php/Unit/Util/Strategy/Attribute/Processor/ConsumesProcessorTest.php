<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Unit\Util\Strategy\Attribute\Processor;

use Itspire\Common\Enum\MimeType;
use Itspire\Exception\Definition\Http\HttpExceptionDefinition;
use Itspire\Exception\Http\HttpException;
use Itspire\FrameworkExtraBundle\Attribute\AttributeInterface;
use Itspire\FrameworkExtraBundle\Attribute\BodyParam;
use Itspire\FrameworkExtraBundle\Attribute\Consumes;
use Itspire\FrameworkExtraBundle\Configuration\CustomRequestAttributes;
use Itspire\FrameworkExtraBundle\Tests\Unit\Fixtures\FixtureController;
use Itspire\FrameworkExtraBundle\Util\MimeTypeMatcherInterface;
use Itspire\FrameworkExtraBundle\Util\Strategy\Attribute\Processor\ConsumesProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ConsumesProcessorTest extends TestCase
{
    use ExpectDeprecationTrait;

    protected MockObject | LoggerInterface | null $loggerMock = null;
    protected MockObject | MimeType | null $mimeTypeMatcherMock = null;
    protected ?ConsumesProcessor $consumesProcessor = null;

    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->mimeTypeMatcherMock = $this->getMockBuilder(MimeTypeMatcherInterface::class)->getMock();

        $this->consumesProcessor = new ConsumesProcessor($this->mimeTypeMatcherMock, $this->loggerMock);
    }

    protected function tearDown(): void
    {
        unset($this->mimeTypeMatcherMock, $this->loggerMock, $this->consumesProcessor);
    }

    public function supportsProvider(): array
    {
        return [
            'notSupported' => [new BodyParam(name: 'param'), false],
            'supported' => [new Consumes([]), true],
        ];
    }

    /**
     * @test
     * @dataProvider supportsProvider
     */
    public function supportsTest($attribute, $result): void
    {
        static::assertEquals(expected: $result, actual: $this->consumesProcessor->supports($attribute));
    }

    /**
     * @test
     * @group legacy
     */
    public function processAlreadyProcessedTest(): void
    {
        $exceptionDefinition = HttpExceptionDefinition::HTTP_INTERNAL_SERVER_ERROR;

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($exceptionDefinition->value);
        $this->expectExceptionMessage($exceptionDefinition->getDescription());
        $this->expectDeprecation(
            'Since itspire/framework-extra-bundle 2.0:'
            . ' Passing anything other than an array to the consumableContentTypes property of "%s" is deprecated'
            . ' and will trigger a TypeError in 3.0'
        );

        $consumes = $this->getConsumes(MimeType::APPLICATION_XML);
        $request = new Request(attributes: [CustomRequestAttributes::CONSUMES_PROCESSED => true]);

        $reflectionMethod = new \ReflectionMethod(FixtureController::class, 'param');

        $this->loggerMock
            ->expects(static::once())
            ->method('error')
            ->with(
                vsprintf(
                    format: 'Duplicate usage of "%s" found on "%s::%s".',
                    values: [
                        $consumes::class,
                        $reflectionMethod->getDeclaringClass()->getName(),
                        $reflectionMethod->getName(),
                    ]
                )
            );

        $this->consumesProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'param'],
                $request,
                HttpKernelInterface::MAIN_REQUEST
            ),
            $consumes
        );
    }

    /** @test */
    public function processUnsupportedMediaTypeTest(): void
    {
        $exceptionDefinition = HttpExceptionDefinition::HTTP_UNSUPPORTED_MEDIA_TYPE;

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($exceptionDefinition->value);
        $this->expectExceptionMessage($exceptionDefinition->getDescription());

        $consumes = $this->getConsumes([MimeType::APPLICATION_XML]);

        $request = new Request(server: ['CONTENT_TYPE' => MimeType::TEXT_HTML->value]);

        $this->mimeTypeMatcherMock
            ->expects(static::once())
            ->method('findMimeTypeMatch')
            ->with([$request->headers->get(key: 'Content-Type')], $consumes->getConsumableContentTypes())
            ->willReturn(null);

        $this->loggerMock
            ->expects(static::once())
            ->method('alert')
            ->with(
                vsprintf(
                    format: 'Unsupported Media Type %s used for body content in route %s.',
                    values: [$request->headers->get(key: 'Content-Type'), $request->attributes->get(key: '_route')]
                )
            );

        $this->consumesProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'param'],
                $request,
                HttpKernelInterface::MAIN_REQUEST
            ),
            $consumes
        );
    }

    public function mimeTypeProvider(): array
    {
        return [
            'enumValue' => [MimeType::APPLICATION_XML],
            'rawValue' => [MimeType::APPLICATION_XML->value]
        ];
    }

    /**
     * @test
     * @dataProvider mimeTypeProvider
     */
    public function processTest(MimeType | string $mimeTypeValue): void
    {
        $consumes = $this->getConsumes([$mimeTypeValue], ['Default', 'extended']);

        $request = new Request(server: ['CONTENT_TYPE' => MimeType::APPLICATION_XML->value]);

        $this->mimeTypeMatcherMock
            ->expects(static::once())
            ->method('findMimeTypeMatch')
            ->with([$request->headers->get(key: 'Content-Type')], $consumes->getConsumableContentTypes())
            ->willReturn(MimeType::APPLICATION_XML->value);

        $this->consumesProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'param'],
                $request,
                HttpKernelInterface::MAIN_REQUEST
            ),
            $consumes
        );

        static::assertTrue(
            condition: $request->attributes->get(key: CustomRequestAttributes::CONSUMES_PROCESSED)
        );
        static::assertEquals(
            expected: ['Default', 'extended'],
            actual: $request->attributes->get(key: CustomRequestAttributes::REQUEST_DESERIALIZATION_GROUPS)
        );
    }

    protected function getConsumes(
        mixed $consumableContentTypes = [],
        mixed $deserializationGroups = []
    ): AttributeInterface {
        return new Consumes($consumableContentTypes, $deserializationGroups);
    }
}
