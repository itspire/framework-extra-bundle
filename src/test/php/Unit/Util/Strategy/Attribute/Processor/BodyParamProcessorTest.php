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
use Itspire\FrameworkExtraBundle\Attribute\BodyParam;
use Itspire\FrameworkExtraBundle\Attribute\Consumes;
use Itspire\FrameworkExtraBundle\Configuration\CustomRequestAttributes;
use Itspire\FrameworkExtraBundle\Tests\TestApp\Model\TestObject;
use Itspire\FrameworkExtraBundle\Tests\Unit\Fixtures\FixtureController;
use Itspire\FrameworkExtraBundle\Util\Strategy\Attribute\Processor\BodyParamProcessor;
use Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck\TypeCheckHandlerInterface;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class BodyParamProcessorTest extends TestCase
{
    protected MockObject | LoggerInterface | null $loggerMock = null;
    protected MockObject | SerializerInterface | null $serializerMock = null;
    protected MockObject | TypeCheckHandlerInterface | null $typeCheckHandlerMock = null;
    protected ?BodyParamProcessor $bodyParamProcessor = null;

    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->serializerMock = $this->getMockBuilder(SerializerInterface::class)->getMock();
        $this->typeCheckHandlerMock = $this->getMockBuilder(TypeCheckHandlerInterface::class)->getMock();

        $this->bodyParamProcessor = new BodyParamProcessor(
            $this->serializerMock,
            $this->loggerMock,
            $this->typeCheckHandlerMock
        );
    }

    protected function tearDown(): void
    {
        unset($this->bodyParamProcessor, $this->loggerMock, $this->serializerMock, $this->typeCheckHandlerMock);
    }

    public static function supportsProvider(): array
    {
        return [
            'notSupported' => [new Consumes(), false],
            'supported' => [new BodyParam(name: 'param'), true],
        ];
    }

    #[Test]
    #[DataProvider('supportsProvider')]
    public function supportsTest($attribute, $result): void
    {
        static::assertEquals(expected: $result, actual: $this->bodyParamProcessor->supports($attribute));
    }

    #[Test]
    public function processAlreadyProcessedTest(): void
    {
        $exceptionDefinition = HttpExceptionDefinition::HTTP_INTERNAL_SERVER_ERROR;

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($exceptionDefinition->value);
        $this->expectExceptionMessage($exceptionDefinition->getDescription());

        $bodyParam = $this->getBodyParam(TestObject::class);

        $request = new Request(attributes: [CustomRequestAttributes::BODYPARAM_PROCESSED => true]);

        $reflectionMethod = new \ReflectionMethod(FixtureController::class, 'param');

        $this->loggerMock
            ->expects($this->once())
            ->method('error')
            ->with(
                vsprintf(
                    format: 'Duplicate usage of "%s" found on "%s::%s".',
                    values: [
                        $bodyParam::class,
                        $reflectionMethod->getDeclaringClass()->getName(),
                        $reflectionMethod->getName(),
                    ]
                )
            );

        $this->bodyParamProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'param'],
                $request,
                HttpKernelInterface::MAIN_REQUEST
            ),
            $bodyParam
        );
    }

    #[Test]
    public function processUnsupportedMediaTypeTest(): void
    {
        $exceptionDefinition = HttpExceptionDefinition::HTTP_UNSUPPORTED_MEDIA_TYPE;

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($exceptionDefinition->value);
        $this->expectExceptionMessage($exceptionDefinition->getDescription());

        $request = new Request(server: ['CONTENT_TYPE' => MimeType::TEXT_HTML->value], content: 'body');

        $this->loggerMock
            ->expects($this->once())
            ->method('alert')
            ->with(
                vsprintf(
                    format: 'Unsupported Media Type "%s" used for body content in route "%s".',
                    values: [$request->headers->get(key: 'Content-Type'), $request->attributes->get(key: '_route')]
                )
            );

        $this->bodyParamProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'param'],
                $request,
                HttpKernelInterface::MAIN_REQUEST
            ),
            $this->getBodyParam(TestObject::class)
        );
    }

    #[Test]
    public function processNoValueTest(): void
    {
        $exceptionDefinition = HttpExceptionDefinition::HTTP_BAD_REQUEST;

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($exceptionDefinition->value);
        $this->expectExceptionMessage($exceptionDefinition->getDescription());

        $bodyParam = $this->getBodyParam(TestObject::class);
        $request = new Request(
            attributes: ['_route' => 'param'],
            server: ['CONTENT_TYPE' => MimeType::APPLICATION_XML->value]
        );

        $this->loggerMock
            ->expects($this->once())
            ->method('alert')
            ->with(
                vsprintf(
                    format: '"%s" defined on route "%s" has no matching "%s" parameter in the request.',
                    values: [$bodyParam::class, $request->attributes->get(key: '_route'), $bodyParam->getName()]
                )
            );

        $this->bodyParamProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'param'],
                $request,
                HttpKernelInterface::MAIN_REQUEST
            ),
            $bodyParam
        );
    }

    #[Test]
    public function processDeserializationErrorTest(): void
    {
        $exceptionDefinition = HttpExceptionDefinition::HTTP_BAD_REQUEST;

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($exceptionDefinition->value);
        $this->expectExceptionMessage($exceptionDefinition->getDescription());

        $xml = '<testObject testProperty2="test"></testObject>';

        $bodyParam = $this->getBodyParam(TestObject::class);

        $request = new Request(
            attributes: ['_route' => 'param'],
            server: ['CONTENT_TYPE' => MimeType::APPLICATION_XML->value],
            content: $xml
        );

        $this->serializerMock
            ->expects($this->once())
            ->method('deserialize')
            ->with(
                $xml,
                TestObject::class,
                $request->getContentTypeFormat(),
                static::isInstanceOf(DeserializationContext::class)
            )
            ->willThrowException(new \Exception());

        $this->loggerMock
            ->expects($this->once())
            ->method('alert')
            ->with(
                vsprintf(
                    format: 'Deserialization to parameter "%s" of type "%s" failed.',
                    values: [$bodyParam->name, $bodyParam->type]
                )
            );

        $this->bodyParamProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'bodyParam'],
                $request,
                HttpKernelInterface::MAIN_REQUEST
            ),
            $bodyParam
        );
    }

    #[Test]
    public function processTest(): void
    {
        $xml = '<testObject testProperty="test" testProperty2=2></testObject>';

        $request = new Request(
            attributes: [
                '_route' => 'param',
                CustomRequestAttributes::REQUEST_DESERIALIZATION_GROUPS => ['Default', 'extended'],
            ],
            server: ['CONTENT_TYPE' => MimeType::APPLICATION_XML->value],
            content: $xml
        );

        $testObject = (new TestObject())->setTestProperty('test')->setTestProperty2(2);

        $this->serializerMock
            ->expects($this->once())
            ->method('deserialize')
            ->with(
                $xml,
                TestObject::class,
                $request->getContentTypeFormat(),
                static::isInstanceOf(DeserializationContext::class)
            )
            ->willReturn($testObject);

        $this->bodyParamProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'param'],
                $request,
                HttpKernelInterface::MAIN_REQUEST
            ),
            $this->getBodyParam(TestObject::class)
        );

        static::assertEquals(expected: $testObject, actual: $request->attributes->get(key: 'param'));
    }

    protected function getBodyParam(string $classFqn): BodyParam
    {
        return new BodyParam(name: 'param', type: 'class', class: $classFqn);
    }
}
