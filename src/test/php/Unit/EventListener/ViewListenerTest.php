<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
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

        $this->event = new ViewEvent($this->kernelMock, new Request(), HttpKernelInterface::MASTER_REQUEST, null);

        $this->viewListener = new ViewListener($this->serializerMock, $this->loggerMock, $this->twigMock);
    }

    protected function tearDown(): void
    {
        unset($this->viewListener, $this->serializerMock, $this->loggerMock, $this->twigMock);

        parent::tearDown();
    }

    /** @test */
    public function onKernelViewHandledRouteNotCalledTest(): void
    {
        $this->viewListener->onKernelView(
            new ViewEvent($this->kernelMock, new Request(), HttpKernelInterface::MASTER_REQUEST, null)
        );

        static::assertNull($this->event->getResponse());
    }

    /** @test */
    public function onKernelViewFileResultTest(): void
    {
        $serverAttributes = ['REQUEST_METHOD' => HttpMethod::GET, 'CONTENT_TYPE' => MimeType::APPLICATION_JSON];
        $request = new Request([], [], [], [], [], $serverAttributes);
        $request->attributes->add(
            [
                CustomRequestAttributes::ROUTE_CALLED => true,
                CustomRequestAttributes::RESPONSE_STATUS_CODE => HttpResponseStatus::HTTP_OK,
            ]
        );

        $filePath = sys_get_temp_dir() . '/test.txt';
        touch($filePath);
        chmod($filePath, 0444);

        $file = new File($filePath);

        $event = new ViewEvent($this->kernelMock, $request, HttpKernelInterface::MASTER_REQUEST, $file);

        $this->viewListener->onKernelView($event);

        /** @var BinaryFileResponse $response */
        $response = $event->getResponse();

        static::assertEquals(HttpResponseStatus::HTTP_OK, $response->getStatusCode());
        static::assertEquals($file, $response->getFile());

        unlink($filePath);
    }

    /** @test */
    public function onKernelViewSerializationErrorTest(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(HttpResponseStatus::HTTP_INTERNAL_SERVER_ERROR);

        $testObject = (new TestObject())->setTestProperty('test')->setTestProperty2(2);

        $serverAttributes = [
            'REQUEST_METHOD' => HttpMethod::GET,
            'CONTENT_TYPE' => MimeType::APPLICATION_JSON,
            'ACCEPT' => MimeType::APPLICATION_JSON,
        ];
        $request = new Request([], [], [], [], [], $serverAttributes);
        $request->attributes->add(
            [
                CustomRequestAttributes::ROUTE_CALLED => true,
                CustomRequestAttributes::RESPONSE_CONTENT_TYPE => MimeType::APPLICATION_JSON,
                CustomRequestAttributes::RESPONSE_FORMAT => $request->getFormat(MimeType::APPLICATION_JSON),
                CustomRequestAttributes::RESPONSE_SERIALIZATION_GROUPS => ['Default'],
            ]
        );

        $this->serializerMock
            ->expects(static::once())
            ->method('serialize')
            ->with($testObject, 'json', static::isInstanceOf(SerializationContext::class))
            ->willThrowException(new \Exception());

        $this->viewListener->onKernelView(
            new ViewEvent($this->kernelMock, $request, HttpKernelInterface::MASTER_REQUEST, $testObject)
        );
    }

    /** @test */
    public function onKernelViewRenderErrorTest(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(HttpResponseStatus::HTTP_INTERNAL_SERVER_ERROR);

        $testObject = (new TestObject())->setTestProperty('test')->setTestProperty2(2);

        $serverAttributes = [
            'REQUEST_METHOD' => HttpMethod::POST,
            'CONTENT_TYPE' => MimeType::APPLICATION_JSON,
            'ACCEPT' => MimeType::TEXT_HTML,
        ];
        $request = new Request([], [], [], [], [], $serverAttributes);
        $request->attributes->add(
            [
                CustomRequestAttributes::ROUTE_CALLED => true,
                CustomRequestAttributes::RESPONSE_CONTENT_TYPE => MimeType::TEXT_HTML,
                CustomRequestAttributes::RESPONSE_FORMAT => $request->getFormat(MimeType::APPLICATION_JSON),
                CustomRequestAttributes::RESPONSE_SERIALIZATION_GROUPS => ['Default'],
            ]
        );

        $json = <<<JSON
            {
                "testProperty": "%s",
                "testProperty2": %d
            }
        JSON;

        $this->serializerMock
            ->expects(static::once())
            ->method('serialize')
            ->with($testObject, 'json', static::isInstanceOf(SerializationContext::class))
            ->willReturn(sprintf($json, $testObject->getTestProperty(), $testObject->getTestProperty2()));

        $this->twigMock
            ->expects(static::once())
            ->method('render')
            ->with(
                '@ItspireFrameworkExtra/response.html.twig',
                [
                    'controllerResult' => sprintf(
                        $json,
                        $testObject->getTestProperty(),
                        $testObject->getTestProperty2()
                    ),
                    'format' => 'json',
                ]
            )
            ->willThrowException(new \Exception());

        $this->viewListener->onKernelView(
            new ViewEvent($this->kernelMock, $request, HttpKernelInterface::MASTER_REQUEST, $testObject)
        );
    }

    /** @test */
    public function onKernelViewRenderHtmlArrayTest(): void
    {
        $testArray = ['testProperty' => 'test', 'testProperty2' => 2];
        $testJson = json_encode($testArray);

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

        $serverAttributes = [
            'REQUEST_METHOD' => HttpMethod::GET,
            'CONTENT_TYPE' => MimeType::APPLICATION_JSON,
            'ACCEPT' => MimeType::TEXT_HTML,
        ];
        $request = new Request([], [], [], [], [], $serverAttributes);
        $request->attributes->add(
            [
                CustomRequestAttributes::ROUTE_CALLED => true,
                CustomRequestAttributes::RESPONSE_CONTENT_TYPE => MimeType::TEXT_HTML,
                CustomRequestAttributes::RESPONSE_FORMAT => $request->getFormat(MimeType::APPLICATION_JSON),
                CustomRequestAttributes::RESPONSE_SERIALIZATION_GROUPS => ['Default'],
            ]
        );

        $this->serializerMock
            ->expects(static::once())
            ->method('serialize')
            ->with($testArray, 'json', static::isInstanceOf(SerializationContext::class))
            ->willReturn($testJson);

        $this->twigMock
            ->expects(static::once())
            ->method('render')
            ->with(
                '@ItspireFrameworkExtra/response.html.twig',
                ['controllerResult' => $testJson, 'format' => 'json']
            )
            ->willReturn($html);

        $event = new ViewEvent($this->kernelMock, $request, HttpKernelInterface::MASTER_REQUEST, $testArray);

        $this->viewListener->onKernelView($event);

        static::assertEquals(HttpResponseStatus::HTTP_OK, $event->getResponse()->getStatusCode());
        static::assertEquals($html, $event->getResponse()->getContent());
    }

    /** @test */
    public function onKernelViewRenderJsonArrayTest(): void
    {
        $testArray = ['testProperty' => 'test', 'testProperty2' => 2];

        $testJson = <<<JSON
            {
                "testProperty":"test",
                "testProperty2":2
            }
        JSON;

        $serverAttributes = [
            'REQUEST_METHOD' => HttpMethod::GET,
            'CONTENT_TYPE' => MimeType::APPLICATION_JSON,
            'ACCEPT' => MimeType::APPLICATION_JSON,
        ];
        $request = new Request([], [], [], [], [], $serverAttributes);
        $request->attributes->add(
            [
                CustomRequestAttributes::ROUTE_CALLED => true,
                CustomRequestAttributes::RESPONSE_CONTENT_TYPE => MimeType::APPLICATION_JSON,
                CustomRequestAttributes::RESPONSE_FORMAT => $request->getFormat(MimeType::APPLICATION_JSON),
                CustomRequestAttributes::RESPONSE_SERIALIZATION_GROUPS => ['Default'],
            ]
        );

        $this->serializerMock
            ->expects(static::once())
            ->method('serialize')
            ->with($testArray, 'json', static::isInstanceOf(SerializationContext::class))
            ->willReturn($testJson);

//        $this->twigMock
//            ->expects(static::once())
//            ->method('render')
//            ->with(
//                '@ItspireFrameworkExtra/response.html.twig',
//                ['controllerResult' => $testJson, 'format' => 'json']
//            )
//            ->willReturn($html);

        $event = new ViewEvent($this->kernelMock, $request, HttpKernelInterface::MASTER_REQUEST, $testArray);

        $this->viewListener->onKernelView($event);

        static::assertEquals(HttpResponseStatus::HTTP_OK, $event->getResponse()->getStatusCode());
        static::assertEquals($testJson, $event->getResponse()->getContent());
    }

    /** @test */
    public function onKernelViewNoContentTest(): void
    {
        $serverAttributes = [
            'REQUEST_METHOD' => HttpMethod::DELETE,
            'CONTENT_TYPE' => MimeType::APPLICATION_JSON,
            'ACCEPT' => MimeType::APPLICATION_JSON,
        ];
        $request = new Request([], [], [], [], [], $serverAttributes);
        $request->attributes->add([CustomRequestAttributes::ROUTE_CALLED => true]);

        $event = new ViewEvent($this->kernelMock, $request, HttpKernelInterface::MASTER_REQUEST, null);

        $this->viewListener->onKernelView($event);

        static::assertEquals(HttpResponseStatus::HTTP_NO_CONTENT, $event->getResponse()->getStatusCode());
    }
}
