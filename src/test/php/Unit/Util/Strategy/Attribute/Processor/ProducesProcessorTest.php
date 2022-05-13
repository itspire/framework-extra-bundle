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
use Itspire\FrameworkExtraBundle\Attribute\ParamAttributeInterface;
use Itspire\FrameworkExtraBundle\Attribute\Produces;
use Itspire\FrameworkExtraBundle\Configuration\CustomRequestAttributes;
use Itspire\FrameworkExtraBundle\Tests\Unit\Fixtures\FixtureController;
use Itspire\FrameworkExtraBundle\Util\MimeTypeMatcherInterface;
use Itspire\FrameworkExtraBundle\Util\Strategy\Attribute\Processor\ProducesProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ProducesProcessorTest extends TestCase
{
    protected MockObject | LoggerInterface | null $loggerMock = null;
    protected MockObject | MimeTypeMatcherInterface | null $mimeTypeMatcherMock = null;
    protected ?ProducesProcessor $producesProcessor = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->mimeTypeMatcherMock = $this->getMockBuilder(MimeTypeMatcherInterface::class)->getMock();

        $this->producesProcessor = new ProducesProcessor($this->mimeTypeMatcherMock, false, $this->loggerMock);
    }

    protected function tearDown(): void
    {
        unset($this->loggerMock, $this->mimeTypeMatcherMock, $this->producesProcessor);

        parent::tearDown();
    }

    public function supportsProvider(): array
    {
        return [
            'notSupported' => [$this->getBodyParam(), false],
            'supported' => [$this->getProduces(), true],
        ];
    }

    /**
     * @test
     * @dataProvider supportsProvider
     */
    public function supportsTest($attribute, $result): void
    {
        static::assertEquals(expected: $result, actual: $this->producesProcessor->supports($attribute));
    }

    /** @test */
    public function processAlreadyProcessedTest(): void
    {
        $exceptionDefinition = HttpExceptionDefinition::HTTP_INTERNAL_SERVER_ERROR;

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($exceptionDefinition->value);
        $this->expectExceptionMessage($exceptionDefinition->getDescription());

        $produces = $this->getProduces([MimeType::APPLICATION_XML]);

        $request = new Request(attributes: [CustomRequestAttributes::PRODUCES_PROCESSED => true]);

        $reflectionMethod = new \ReflectionMethod(FixtureController::class, 'param');

        $this->loggerMock
            ->expects(static::once())
            ->method('error')
            ->with(
                vsprintf(
                    format: 'Duplicate usage of "%s" found on "%s::%s".',
                    values: [
                        $produces::class,
                        $reflectionMethod->getDeclaringClass()->getName(),
                        $reflectionMethod->getName(),
                    ]
                )
            );

        $this->producesProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'param'],
                $request,
                HttpKernelInterface::MAIN_REQUEST
            ),
            $produces
        );
    }

    /** @test */
    public function processUnsupportedMediaTypeTest(): void
    {
        $exceptionDefinition = HttpExceptionDefinition::HTTP_NOT_ACCEPTABLE;

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($exceptionDefinition->value);
        $this->expectExceptionMessage($exceptionDefinition->getDescription());

        $produces = $this->getProduces([MimeType::APPLICATION_XML]);

        $request = new Request(server: ['HTTP_ACCEPT' => MimeType::TEXT_HTML->value]);

        $this->mimeTypeMatcherMock
            ->expects(static::once())
            ->method('findMimeTypeMatch')
            ->with($request->getAcceptableContentTypes(), [MimeType::APPLICATION_XML->value])
            ->willReturn(null);

        $this->loggerMock
            ->expects(static::once())
            ->method('alert')
            ->with(
                vsprintf(
                    format: 'Unsupported Media Type(s) used for acceptable response content type in route %s (%s).',
                    values: [
                        $request->attributes->get(key: '_route'),
                        implode(separator: ', ', array: $request->getAcceptableContentTypes()),
                    ]
                )
            );

        $this->producesProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'param'],
                $request,
                HttpKernelInterface::MAIN_REQUEST
            ),
            $produces
        );
    }

    public function processProvider(): array
    {
        return [
            'xmlWithHtmlNotAcceptable' => [
                false,
                MimeType::APPLICATION_XML->value,
                'xml',
                [MimeType::APPLICATION_XML->value],
            ],
            'jsonWithHtmlNotAcceptable' => [
                false,
                MimeType::APPLICATION_JSON->value,
                'json',
                [MimeType::APPLICATION_JSON->value],
            ],
            'xmlWithHtmlAcceptable' => [
                true,
                MimeType::APPLICATION_XML->value,
                'xml',
                [MimeType::APPLICATION_XML->value],
            ],
            'htmlWithHtmlAcceptable' => [true, MimeType::TEXT_HTML->value, 'json', [MimeType::TEXT_HTML->value]],
        ];
    }

    /**
     * @test
     * @dataProvider processProvider
     */
    public function processTest(
        bool $isHtmlAcceptable,
        string $requestAccept,
        string $responseFormat,
        array $acceptableFormats
    ): void {
        $produces = $this->getProduces([$requestAccept], ['Default', 'extended']);

        $request = new Request(
            query: $isHtmlAcceptable ? ['renderHtml' => 'true'] : [],
            server: ['HTTP_ACCEPT' => $requestAccept]
        );

        $this->mimeTypeMatcherMock
            ->expects(static::once())
            ->method('findMimeTypeMatch')
            ->with($request->getAcceptableContentTypes(), $acceptableFormats)
            ->willReturn($requestAccept);

        $producesProcessor = new ProducesProcessor($this->mimeTypeMatcherMock, $isHtmlAcceptable, $this->loggerMock);

        $producesProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'param'],
                $request,
                HttpKernelInterface::MAIN_REQUEST
            ),
            $produces
        );

        static::assertTrue(
            condition: $request->attributes->get(key: CustomRequestAttributes::PRODUCES_PROCESSED)
        );
        static::assertEquals(expected: ['Default', 'extended'], actual: $produces->getSerializationGroups());
        static::assertEquals(
            expected: $responseFormat,
            actual: $request->attributes->get(key: CustomRequestAttributes::RESPONSE_FORMAT)
        );
        static::assertEquals(
            expected: $isHtmlAcceptable ? MimeType::TEXT_HTML->value : $requestAccept,
            actual: $request->attributes->get(key: CustomRequestAttributes::RESPONSE_CONTENT_TYPE)
        );
    }

    protected function getBodyParam(): ParamAttributeInterface
    {
        return new BodyParam(name: 'param');
    }

    protected function getProduces(mixed $acceptableFormats = [], mixed $serializationGroups = []): AttributeInterface
    {
        return new Produces($acceptableFormats, $serializationGroups);
    }
}
