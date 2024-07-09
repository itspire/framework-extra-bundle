<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Unit\EventListener;

use Itspire\Common\Enum\Http\HttpMethod;
use Itspire\Common\Enum\Http\HttpResponseStatus;
use Itspire\Common\Enum\MimeType;
use Itspire\Exception\Http\HttpException;
use Itspire\FrameworkExtraBundle\Configuration\CustomRequestAttributes;
use Itspire\FrameworkExtraBundle\EventListener\ViewListener;
use Itspire\FrameworkExtraBundle\Tests\TestApp\Model\TestObject;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Twig\Environment;

class ViewListenerTest extends TestCase
{
    private ?MockObject $serializerMock = null;
    private ?MockObject $loggerMock = null;
    private ?MockObject $twigMock = null;
    private ?MockObject $kernelMock = null;
    private ?ViewListener $viewListener = null;
    private ?ViewEvent $event = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->serializerMock = $this->getMockBuilder(SerializerInterface::class)->getMock();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->twigMock = $this->getMockBuilder(Environment::class)->disableOriginalConstructor()->getMock();
        $this->kernelMock = $this->getMockBuilder(HttpKernelInterface::class)->getMock();

        $this->event = new ViewEvent($this->kernelMock, new Request(), HttpKernelInterface::MAIN_REQUEST, null);

        $this->viewListener = new ViewListener($this->serializerMock, $this->loggerMock, $this->twigMock);
    }

    protected function tearDown(): void
    {
        unset($this->viewListener, $this->serializerMock, $this->loggerMock, $this->twigMock);

        parent::tearDown();
    }

    #[Test]
    public function onKernelViewHandledRouteNotCalledTest(): void
    {
        $this->viewListener->onKernelView(
            new ViewEvent($this->kernelMock, new Request(), HttpKernelInterface::MAIN_REQUEST, null)
        );

        static::assertNull(actual: $this->event->getResponse());
    }

    #[Test]
    public function onKernelViewFileResultTest(): void
    {
        $request = new Request(
            attributes: [
                CustomRequestAttributes::ROUTE_CALLED => true,
                CustomRequestAttributes::RESPONSE_STATUS_CODE => HttpResponseStatus::HTTP_OK->value,
            ],
            server: [
                'REQUEST_METHOD' => HttpMethod::GET->value,
                'CONTENT_TYPE' => MimeType::APPLICATION_JSON->value,
            ]
        );

        $filePath = sys_get_temp_dir() . '/test.txt';
        touch($filePath);
        chmod($filePath, 0444);

        $file = new File($filePath);

        $event = new ViewEvent($this->kernelMock, $request, HttpKernelInterface::MAIN_REQUEST, $file);

        $this->viewListener->onKernelView($event);

        /** @var BinaryFileResponse $response */
        $response = $event->getResponse();

        static::assertEquals(expected: HttpResponseStatus::HTTP_OK->value, actual: $response->getStatusCode());
        static::assertEquals(expected: $file, actual: $response->getFile());

        unlink($filePath);
    }

    #[Test]
    public function onKernelViewSerializationErrorTest(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(HttpResponseStatus::HTTP_INTERNAL_SERVER_ERROR->value);

        $testObject = (new TestObject())->setTestProperty('test')->setTestProperty2(2);

        $request = new Request(
            attributes: [
                CustomRequestAttributes::ROUTE_CALLED => true,
                CustomRequestAttributes::RESPONSE_CONTENT_TYPE => MimeType::APPLICATION_JSON->value,
                CustomRequestAttributes::RESPONSE_SERIALIZATION_GROUPS => ['Default'],
            ],
            server: [
                'REQUEST_METHOD' => HttpMethod::GET->value,
                'CONTENT_TYPE' => MimeType::APPLICATION_JSON->value,
                'ACCEPT' => MimeType::APPLICATION_JSON->value,
            ]
        );

        $request->attributes->set(
            key: CustomRequestAttributes::RESPONSE_FORMAT,
            value: $request->getFormat(MimeType::APPLICATION_JSON->value)
        );

        $this->serializerMock
            ->expects($this->once())
            ->method('serialize')
            ->with($testObject, 'json', static::isInstanceOf(SerializationContext::class))
            ->willThrowException(new \Exception());

        $this->viewListener->onKernelView(
            new ViewEvent($this->kernelMock, $request, HttpKernelInterface::MAIN_REQUEST, $testObject)
        );
    }

    #[Test]
    public function onKernelViewRenderErrorTest(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(HttpResponseStatus::HTTP_INTERNAL_SERVER_ERROR->value);

        $testObject = (new TestObject())->setTestProperty('test')->setTestProperty2(2);

        $request = new Request(
            attributes: [
                CustomRequestAttributes::ROUTE_CALLED => true,
                CustomRequestAttributes::RESPONSE_CONTENT_TYPE => MimeType::TEXT_HTML->value,
                CustomRequestAttributes::RESPONSE_SERIALIZATION_GROUPS => ['Default'],
            ],
            server: [
                'REQUEST_METHOD' => HttpMethod::POST->value,
                'CONTENT_TYPE' => MimeType::APPLICATION_JSON->value,
                'ACCEPT' => MimeType::TEXT_HTML->value,
            ]
        );

        $request->attributes->set(
            key: CustomRequestAttributes::RESPONSE_FORMAT,
            value: $request->getFormat(MimeType::APPLICATION_JSON->value)
        );

        $json = vsprintf(
            format:  <<<JSON
                {
                    "testProperty": "%s",
                    "testProperty2": %d
                }
            JSON,
            values: [$testObject->getTestProperty(), $testObject->getTestProperty2()]
        );

        $this->serializerMock
            ->expects($this->once())
            ->method('serialize')
            ->with($testObject, 'json', static::isInstanceOf(SerializationContext::class))
            ->willReturn($json);

        $this->twigMock
            ->expects($this->once())
            ->method('render')
            ->with('@ItspireFrameworkExtra/response.html.twig', ['controllerResult' => $json, 'format' => 'json'])
            ->willThrowException(new \Exception());

        $this->viewListener->onKernelView(
            new ViewEvent($this->kernelMock, $request, HttpKernelInterface::MAIN_REQUEST, $testObject)
        );
    }

    #[Test]
    public function onKernelViewRenderHtmlArrayTest(): void
    {
        $testArray = ['testProperty' => 'test', 'testProperty2' => 2];
        $testJson = json_encode($testArray, JSON_THROW_ON_ERROR);

        $html = <<<HTML
            <html lang="fr">
                <body>
                    <pre lang="json">
                        {
                            "testProperty":"test",
                            "testProperty2":2
                        }
                    </pre>
                </body>
            </html>
        HTML;

        $request = new Request(
            attributes: [
                CustomRequestAttributes::ROUTE_CALLED => true,
                CustomRequestAttributes::RESPONSE_CONTENT_TYPE => MimeType::TEXT_HTML->value,
                CustomRequestAttributes::RESPONSE_SERIALIZATION_GROUPS => ['Default'],
            ],
            server: [
                'REQUEST_METHOD' => HttpMethod::GET->value,
                'CONTENT_TYPE' => MimeType::APPLICATION_JSON->value,
                'ACCEPT' => MimeType::TEXT_HTML->value,
            ]
        );

        $request->attributes->set(
            key: CustomRequestAttributes::RESPONSE_FORMAT,
            value: $request->getFormat(MimeType::APPLICATION_JSON->value)
        );

        $this->serializerMock
            ->expects($this->once())
            ->method('serialize')
            ->with($testArray, 'json', static::isInstanceOf(SerializationContext::class))
            ->willReturn($testJson);

        $this->twigMock
            ->expects($this->once())
            ->method('render')
            ->with('@ItspireFrameworkExtra/response.html.twig', ['controllerResult' => $testJson, 'format' => 'json'])
            ->willReturn($html);

        $event = new ViewEvent($this->kernelMock, $request, HttpKernelInterface::MAIN_REQUEST, $testArray);

        $this->viewListener->onKernelView($event);

        static::assertEquals(
            expected: HttpResponseStatus::HTTP_OK->value,
            actual: $event->getResponse()->getStatusCode()
        );
        static::assertEquals(expected: $html, actual: $event->getResponse()->getContent());
    }

    #[Test]
    public function onKernelViewRenderJsonArrayTest(): void
    {
        $testArray = ['testProperty' => 'test', 'testProperty2' => 2];

        $testJson = <<<JSON
            {
                "testProperty":"test",
                "testProperty2":2
            }
        JSON;

        $request = new Request(
            attributes: [
                CustomRequestAttributes::ROUTE_CALLED => true,
                CustomRequestAttributes::RESPONSE_CONTENT_TYPE => MimeType::APPLICATION_JSON->value,
                CustomRequestAttributes::RESPONSE_SERIALIZATION_GROUPS => ['Default'],
            ],
            server: [
                'REQUEST_METHOD' => HttpMethod::GET->value,
                'CONTENT_TYPE' => MimeType::APPLICATION_JSON->value,
                'ACCEPT' => MimeType::APPLICATION_JSON->value,
            ]
        );

        $request->attributes->set(
            key: CustomRequestAttributes::RESPONSE_FORMAT,
            value: $request->getFormat(MimeType::APPLICATION_JSON->value)
        );

        $this->serializerMock
            ->expects($this->once())
            ->method('serialize')
            ->with($testArray, 'json', static::isInstanceOf(SerializationContext::class))
            ->willReturn($testJson);

        $event = new ViewEvent($this->kernelMock, $request, HttpKernelInterface::MAIN_REQUEST, $testArray);

        $this->viewListener->onKernelView($event);

        static::assertEquals(
            expected: HttpResponseStatus::HTTP_OK->value,
            actual: $event->getResponse()->getStatusCode()
        );
        static::assertEquals(expected: $testJson, actual: $event->getResponse()->getContent());
    }

    #[Test]
    public function onKernelViewNoContentTest(): void
    {
        $request = new Request(
            attributes: [CustomRequestAttributes::ROUTE_CALLED => true],
            server: [
                'REQUEST_METHOD' => HttpMethod::DELETE->value,
                'CONTENT_TYPE' => MimeType::APPLICATION_JSON->value,
                'ACCEPT' => MimeType::APPLICATION_JSON->value,
            ]
        );

        $event = new ViewEvent($this->kernelMock, $request, HttpKernelInterface::MAIN_REQUEST, null);

        $this->viewListener->onKernelView($event);

        static::assertEquals(
            expected: HttpResponseStatus::HTTP_NO_CONTENT->value,
            actual: $event->getResponse()->getStatusCode()
        );
    }
}
