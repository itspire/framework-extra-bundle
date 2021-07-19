<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Unit\Util\Strategy\Annotation\Processor;

use Itspire\Common\Enum\MimeType;
use Itspire\Exception\Definition\Http\HttpExceptionDefinition;
use Itspire\Exception\Http\HttpException;
use Itspire\FrameworkExtraBundle\Annotation\BodyParam;
use Itspire\FrameworkExtraBundle\Annotation\Produces;
use Itspire\FrameworkExtraBundle\Configuration\CustomRequestAttributes;
use Itspire\FrameworkExtraBundle\Tests\Unit\Fixtures\FixtureController;
use Itspire\FrameworkExtraBundle\Util\MimeTypeMatcherInterface;
use Itspire\FrameworkExtraBundle\Util\Strategy\Annotation\Processor\ProducesProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ProducesProcessorTest extends TestCase
{
    private ?MockObject $loggerMock = null;
    private ?MockObject $mimeTypeMatcherMock = null;
    private ?ProducesProcessor $producesProcessor = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->mimeTypeMatcherMock = $this->getMockBuilder(MimeTypeMatcherInterface::class)->getMock();

        $this->producesProcessor = new ProducesProcessor($this->loggerMock, $this->mimeTypeMatcherMock, false);
    }

    protected function tearDown(): void
    {
        unset($this->loggerMock, $this->mimeTypeMatcherMock, $this->producesProcessor);

        parent::tearDown();
    }

    /** @test */
    public function supportsFalseTest(): void
    {
        static::assertFalse($this->producesProcessor->supports(new BodyParam([])));
    }

    /** @test */
    public function supportsTrueTest(): void
    {
        static::assertTrue($this->producesProcessor->supports(new Produces([])));
    }

    /** @test */
    public function processAlreadyProcessedTest(): void
    {
        $exceptionDefinition = new HttpExceptionDefinition(HttpExceptionDefinition::HTTP_INTERNAL_SERVER_ERROR);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($exceptionDefinition->getValue());
        $this->expectExceptionMessage($exceptionDefinition->getDescription());

        $annotation = new Produces(['value' => MimeType::APPLICATION_XML]);

        $request = new Request();
        $request->attributes->set(CustomRequestAttributes::PRODUCES_ANNOTATION_PROCESSED, true);

        $reflectionClass = new \ReflectionClass(FixtureController::class);
        $reflectionMethod = $reflectionClass->getMethod('param');

        $this->loggerMock
            ->expects(static::once())
            ->method('error')
            ->with(
                sprintf(
                    'Duplicate @Produces annotation found on %s::%s.',
                    $reflectionClass->getName(),
                    $reflectionMethod->getName()
                )
            );

        $this->producesProcessor->process(
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
        $exceptionDefinition = new HttpExceptionDefinition(HttpExceptionDefinition::HTTP_NOT_ACCEPTABLE);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($exceptionDefinition->getValue());
        $this->expectExceptionMessage($exceptionDefinition->getDescription());

        $annotation = new Produces(['value' => MimeType::APPLICATION_XML]);

        $request = new Request([], [], [], [], [], ['HTTP_ACCEPT' => MimeType::TEXT_HTML]);

        $this->mimeTypeMatcherMock
            ->expects(static::once())
            ->method('findMimeTypeMatch')
            ->with($request->getAcceptableContentTypes(), [MimeType::APPLICATION_XML])
            ->willReturn(null);

        $this->loggerMock
            ->expects(static::once())
            ->method('alert')
            ->with(
                sprintf(
                    'Unsupported Media Type(s) used for acceptable response content type in route %s (%s).',
                    $request->attributes->get('_route'),
                    implode(', ', $request->getAcceptableContentTypes())
                )
            );

        $this->producesProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'param'],
                $request,
                HttpKernelInterface::MASTER_REQUEST
            ),
            $annotation
        );
    }

    public function processProvider(): array
    {
        return [
            'xmlWithHtmlNotAcceptable' => [false, MimeType::APPLICATION_XML, 'xml', [MimeType::APPLICATION_XML]],
            'jsonWithHtmlNotAcceptable' => [false, MimeType::APPLICATION_JSON, 'json', [MimeType::APPLICATION_JSON]],
            'xmlWithHtmlAcceptable' => [true, MimeType::APPLICATION_XML, 'xml', [MimeType::APPLICATION_XML]],
            'htmlWithHtmlAcceptable' => [true, MimeType::TEXT_HTML, 'json', [MimeType::TEXT_HTML]],
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
        $annotation = new Produces(['value' => $requestAccept, 'serializationGroups' => ['Default', 'extended']]);

        $queryParams = $isHtmlAcceptable ? ['renderHtml' => true] : [];

        $request = new Request($queryParams, [], [], [], [], ['HTTP_ACCEPT' => $requestAccept]);

        $this->mimeTypeMatcherMock
            ->expects(static::once())
            ->method('findMimeTypeMatch')
            ->with($request->getAcceptableContentTypes(), $acceptableFormats)
            ->willReturn($requestAccept);

        $producesProcessor = new ProducesProcessor($this->loggerMock, $this->mimeTypeMatcherMock, $isHtmlAcceptable);

        $producesProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'param'],
                $request,
                HttpKernelInterface::MASTER_REQUEST
            ),
            $annotation
        );

        static::assertTrue($request->attributes->get(CustomRequestAttributes::PRODUCES_ANNOTATION_PROCESSED));
        static::assertEquals(['Default', 'extended'], $annotation->getSerializationGroups());
        static::assertEquals($responseFormat, $request->attributes->get(CustomRequestAttributes::RESPONSE_FORMAT));
        static::assertEquals(
            $isHtmlAcceptable ? MimeType::TEXT_HTML : $requestAccept,
            $request->attributes->get(CustomRequestAttributes::RESPONSE_CONTENT_TYPE)
        );
    }
}
