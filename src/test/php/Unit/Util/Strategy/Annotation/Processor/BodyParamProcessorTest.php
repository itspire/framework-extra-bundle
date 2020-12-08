<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Unit\Util\Strategy\Annotation\Processor;

use Itspire\Common\Enum\MimeType;
use Itspire\Exception\Http\Definition\HttpExceptionDefinition;
use Itspire\Exception\Http\HttpException;
use Itspire\FrameworkExtraBundle\Annotation\BodyParam;
use Itspire\FrameworkExtraBundle\Annotation\Consumes;
use Itspire\FrameworkExtraBundle\Configuration\CustomRequestAttributes;
use Itspire\FrameworkExtraBundle\Tests\TestApp\Model\TestObject;
use Itspire\FrameworkExtraBundle\Tests\Unit\Fixtures\FixtureController;
use Itspire\FrameworkExtraBundle\Util\Strategy\Annotation\Processor\BodyParamProcessor;
use Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck\TypeCheckHandlerInterface;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class BodyParamProcessorTest extends TestCase
{
    private ?MockObject $loggerMock = null;
    private ?MockObject $serializerMock = null;
    private ?BodyParamProcessor $bodyParamProcessor = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $typeCheckHandlerMock = $this->getMockBuilder(TypeCheckHandlerInterface::class)->getMock();
        $this->serializerMock = $this->getMockBuilder(SerializerInterface::class)->getMock();

        $this->bodyParamProcessor = new BodyParamProcessor(
            $this->loggerMock,
            $typeCheckHandlerMock,
            $this->serializerMock
        );
    }

    protected function tearDown(): void
    {
        unset($this->loggerMock, $this->serializerMock, $this->bodyParamProcessor);

        parent::tearDown();
    }

    public function supportsProvider(): array
    {
        return [
            'notSupported' => [new Consumes([]), false],
            'supported' => [new BodyParam([]), true],
        ];
    }

    /**
     * @test
     * @dataProvider supportsProvider
     */
    public function supportsTest($type, $result): void
    {
        static::assertEquals($result, $this->bodyParamProcessor->supports($type));
    }

    /** @test */
    public function processAlreadyProcessedTest(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(HttpExceptionDefinition::HTTP_INTERNAL_SERVER_ERROR[0]);
        $this->expectExceptionMessage(HttpExceptionDefinition::HTTP_INTERNAL_SERVER_ERROR[1]);

        $annotation = new BodyParam(['name' => 'param']);

        $request = new Request();
        $request->attributes->set(CustomRequestAttributes::BODY_PARAM_ANNOTATION_PROCESSED, true);

        $reflectionClass = new \ReflectionClass(FixtureController::class);
        $reflectionMethod = $reflectionClass->getMethod('param');

        $this->loggerMock
            ->expects(static::once())
            ->method('error')
            ->with(
                sprintf(
                    'Duplicate @BodyParam annotation found on %s::%s.',
                    $reflectionClass->getName(),
                    $reflectionMethod->getName()
                )
            );

        $this->bodyParamProcessor->process(
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

        $annotation = new BodyParam(['name' => 'param', 'type' => 'class', 'class' => \stdClass::class]);
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => MimeType::TEXT_HTML], 'body');

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

        $this->bodyParamProcessor->process(
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
    public function processNoValueTest(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(HttpExceptionDefinition::HTTP_BAD_REQUEST[0]);
        $this->expectExceptionMessage(HttpExceptionDefinition::HTTP_BAD_REQUEST[1]);

        $annotation = new BodyParam(['name' => 'param', 'type' => 'class', 'class' => TestObject::class]);

        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => MimeType::APPLICATION_XML]);
        $request->attributes->set('_route', 'param');

        $this->loggerMock
            ->expects(static::once())
            ->method('alert')
            ->with(
                sprintf(
                    '@BodyParam annotation is defined on route %s but the corresponding value was not in the request.',
                    $request->attributes->get('_route')
                )
            );

        $this->bodyParamProcessor->process(
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
    public function processDeserializationErrorTest(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(HttpExceptionDefinition::HTTP_BAD_REQUEST[0]);
        $this->expectExceptionMessage(HttpExceptionDefinition::HTTP_BAD_REQUEST[1]);

        $xml = '<testObject testProperty2="test"></testObject>';

        $annotation = new BodyParam(['name' => 'param', 'type' => 'class', 'class' => TestObject::class]);

        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => MimeType::APPLICATION_XML], $xml);
        $request->attributes->set('_route', 'param');

        $this->serializerMock
            ->expects(static::once())
            ->method('deserialize')
            ->with(
                $xml,
                TestObject::class,
                $request->getContentType(),
                static::isInstanceOf(DeserializationContext::class)
            )
            ->willThrowException(new \Exception());

        $this->loggerMock
            ->expects(static::once())
            ->method('alert')
            ->with(
                sprintf(
                    'Deserialization to parameter %s of type %s failed.',
                    $annotation->getName(),
                    $annotation->getType()
                )
            );

        $this->bodyParamProcessor->process(
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
        $xml = '<testObject testProperty="test" testProperty2=2></testObject>';

        $annotation = new BodyParam(['name' => 'param', 'type' => 'class', 'class' => TestObject::class]);

        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => MimeType::APPLICATION_XML], $xml);
        $request->attributes->set('_route', 'param');
        $request->attributes->set(CustomRequestAttributes::REQUEST_DESERIALIZATION_GROUPS, ['Default', 'extended']);

        $testObject = (new TestObject())->setTestProperty('test')->setTestProperty2(2);

        $this->serializerMock
            ->expects(static::once())
            ->method('deserialize')
            ->with(
                $xml,
                TestObject::class,
                $request->getContentType(),
                static::isInstanceOf(DeserializationContext::class)
            )
            ->willReturn($testObject);

        $this->bodyParamProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'param'],
                $request,
                HttpKernelInterface::MASTER_REQUEST
            ),
            $annotation
        );

        static::assertEquals($testObject, $request->attributes->get('param'));
    }
}
