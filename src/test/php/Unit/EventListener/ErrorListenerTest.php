<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Unit\EventListener;

use Itspire\Common\Enum\Http\HttpResponseStatus;
use Itspire\Common\Enum\MimeType;
use Itspire\Exception\Api\Adapter\ExceptionApiAdapterInterface;
use Itspire\Exception\Api\Mapper\ExceptionApiMapperInterface;
use Itspire\Exception\Api\Model as ApiExceptionModel;
use Itspire\Exception\Definition\Http\HttpExceptionDefinition;
use Itspire\Exception\Definition\Webservice\WebserviceExceptionDefinition;
use Itspire\Exception\ExceptionInterface;
use Itspire\Exception\Http\HttpException;
use Itspire\Exception\Webservice\WebserviceException;
use Itspire\FrameworkExtraBundle\Configuration\CustomRequestAttributes;
use Itspire\FrameworkExtraBundle\EventListener\ErrorListener;
use JMS\Serializer\SerializerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Twig\Environment;

class ErrorListenerTest extends TestCase
{
    private ?MockObject $loggerMock = null;
    private ?MockObject $twigMock = null;
    private ?MockObject $serializerMock = null;
    private ?MockObject $exceptionMapperMock = null;
    private ?MockObject $exceptionAdapterMock = null;
    private ?ErrorListener $errorListener = null;
    private ?ExceptionEvent $event = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->twigMock = $this->getMockBuilder(Environment::class)->disableOriginalConstructor()->getMock();
        $this->serializerMock = $this->getMockBuilder(SerializerInterface::class)->getMock();
        $this->exceptionMapperMock = $this->getMockBuilder(ExceptionApiMapperInterface::class)->getMock();
        $this->exceptionAdapterMock = $this->getMockBuilder(ExceptionApiAdapterInterface::class)->getMock();

        $this->event = new ExceptionEvent(
            $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            new HttpException(
                new HttpExceptionDefinition(HttpExceptionDefinition::HTTP_CONFLICT)
            )
        );

        $this->errorListener = new ErrorListener($this->loggerMock, $this->twigMock, $this->serializerMock);
    }

    protected function tearDown(): void
    {
        unset($this->errorListener, $this->loggerMock, $this->twigMock, $this->serializerMock);

        parent::tearDown();
    }

    /** @test */
    public function registerMapperTest(): void
    {
        $reflectionClass = new \ReflectionClass(ErrorListener::class);
        $reflectionProperty = $reflectionClass->getProperty('exceptionApiMappers');
        $reflectionProperty->setAccessible(true);

        static::assertCount(0, $reflectionProperty->getValue($this->errorListener));

        $this->errorListener->registerMapper($this->exceptionMapperMock);
        static::assertCount(1, $reflectionProperty->getValue($this->errorListener));

        $this->errorListener->registerMapper($this->exceptionMapperMock);
        static::assertCount(1, $reflectionProperty->getValue($this->errorListener));
    }

    /** @test */
    public function registerAdapterTest(): void
    {
        $reflectionClass = new \ReflectionClass(ErrorListener::class);
        $reflectionProperty = $reflectionClass->getProperty('exceptionApiAdapters');
        $reflectionProperty->setAccessible(true);

        static::assertCount(0, $reflectionProperty->getValue($this->errorListener));

        $this->errorListener->registerAdapter($this->exceptionAdapterMock);
        static::assertCount(1, $reflectionProperty->getValue($this->errorListener));

        $this->errorListener->registerAdapter($this->exceptionAdapterMock);
        static::assertCount(1, $reflectionProperty->getValue($this->errorListener));
    }

    /** @test */
    public function onKernelExceptionHandledRouteNotCalledTest(): void
    {
        $this->errorListener->onKernelException($this->event);

        static::assertNull($this->event->getResponse());
    }

    /** @test */
    public function onKernelExceptionNotHandledExceptionTest(): void
    {
        $this->event->getRequest()->attributes->set(CustomRequestAttributes::ROUTE_CALLED, true);

        $this->errorListener->onKernelException($this->event);

        static::assertNull($this->event->getResponse());
    }

    /** @test */
    public function onKernelExceptionNoMapperNorAdapterTest(): void
    {
        $request = $this->event->getRequest();
        /** @var ExceptionInterface $exception */
        $exception = $this->event->getThrowable();
        $exceptionDefinition = $exception->getExceptionDefinition();

        $request->attributes->set(CustomRequestAttributes::ROUTE_CALLED, true);
        $request->attributes->set(CustomRequestAttributes::RESPONSE_CONTENT_TYPE, MimeType::APPLICATION_XML);
        $request->attributes->set(CustomRequestAttributes::RESPONSE_FORMAT, 'xml');

        $this->loggerMock
            ->expects(static::at(0))
            ->method('notice')
            ->with(
                sprintf(
                    'No HttpResponseStatus mapping found for %s exception definition : %d - %s.',
                    get_class($exceptionDefinition),
                    $exceptionDefinition->getCode(),
                    $exceptionDefinition->getDescription()
                ),
                ['exceptionDefinition' => $exceptionDefinition]
            );

        $this->loggerMock->expects(static::atMost(1))->method('notice');

        $this->errorListener->onKernelException($this->event);

        $response = $this->event->getResponse();

        static::assertEquals(HttpResponseStatus::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
    }

    /** @test */
    public function onKernelExceptionNoMatchingMapperNorAdapterTest(): void
    {
        $this->event->setThrowable(
            new WebserviceException(
                new WebserviceExceptionDefinition(WebserviceExceptionDefinition::CONFLICT)
            )
        );

        $request = $this->event->getRequest();
        /** @var ExceptionInterface $exception */
        $exception = $this->event->getThrowable();
        $exceptionDefinition = $exception->getExceptionDefinition();

        $request->attributes->set(CustomRequestAttributes::ROUTE_CALLED, true);
        $request->attributes->set(CustomRequestAttributes::RESPONSE_CONTENT_TYPE, MimeType::APPLICATION_XML);
        $request->attributes->set(CustomRequestAttributes::RESPONSE_FORMAT, 'xml');

        $this->exceptionMapperMock
            ->expects(static::once())
            ->method('supports')
            ->with($exceptionDefinition)
            ->willReturn(false);

        $this->exceptionAdapterMock->expects(static::once())->method('supports')->with($exception)->willReturn(false);

        $this->loggerMock
            ->expects(static::at(0))
            ->method('notice')
            ->with(
                sprintf(
                    'No HttpResponseStatus mapping found for %s exception definition : %d - %s.',
                    get_class($exceptionDefinition),
                    $exceptionDefinition->getCode(),
                    $exceptionDefinition->getDescription()
                ),
                ['exceptionDefinition' => $exceptionDefinition]
            );

        $this->loggerMock
            ->expects(static::at(1))
            ->method('notice')
            ->with(
                sprintf(
                    'No adapter found for %s exception : %d - %s.',
                    get_class($exception),
                    $exception->getCode(),
                    $exception->getMessage()
                ),
                ['exception' => $exception]
            );

        $this->errorListener
            ->registerMapper($this->exceptionMapperMock)
            ->registerAdapter($this->exceptionAdapterMock)
            ->onKernelException($this->event);

        $response = $this->event->getResponse();

        static::assertEquals(HttpResponseStatus::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
    }

    /** @test */
    public function onKernelExceptionHtmlRenderingErrorTest(): void
    {
        $this->event->setThrowable(
            new WebserviceException(
                new WebserviceExceptionDefinition(WebserviceExceptionDefinition::CONFLICT)
            )
        );

        $request = $this->event->getRequest();
        /** @var ExceptionInterface $exception */
        $exception = $this->event->getThrowable();
        $exceptionDefinition = $exception->getExceptionDefinition();

        $request->attributes->set(CustomRequestAttributes::ROUTE_CALLED, true);
        $request->attributes->set(CustomRequestAttributes::RESPONSE_CONTENT_TYPE, MimeType::TEXT_HTML);
        $request->attributes->set(CustomRequestAttributes::RESPONSE_FORMAT, 'json');

        $httpResponseStatus = new HttpResponseStatus(HttpResponseStatus::HTTP_FORBIDDEN);

        $webserviceApiException = (new ApiExceptionModel\Webservice\WebserviceExceptionApi())
            ->setCode((string) $exception->getCode())
            ->setMessage($exception->getMessage());

        $this->exceptionMapperMock
            ->expects(static::at(0))
            ->method('supports')
            ->with($exceptionDefinition)
            ->willReturn(true);

        $this->exceptionMapperMock
            ->expects(static::at(1))
            ->method('map')
            ->with($exceptionDefinition)
            ->willReturn($httpResponseStatus);

        $this->exceptionAdapterMock
            ->expects(static::at(0))
            ->method('supports')
            ->with($exception)
            ->willReturn(true);

        $this->exceptionAdapterMock
            ->expects(static::at(1))
            ->method('adaptBusinessExceptionToApiException')
            ->with($exception)
            ->willReturn($webserviceApiException);

        $this->twigMock
            ->expects(static::once())
            ->method('render')
            ->with('@ItspireFrameworkExtra/response.html.twig', ['controllerResult' => '', 'format' => 'json'])
            ->willThrowException(new \Exception());

        $this->errorListener
            ->registerMapper($this->exceptionMapperMock)
            ->registerAdapter($this->exceptionAdapterMock)
            ->onKernelException($this->event);

        $response = $this->event->getResponse();

        static::assertEquals(HttpResponseStatus::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
    }

    /** @test */
    public function onKernelExceptionNoHtmlRenderingTest(): void
    {
        $request = $this->event->getRequest();
        /** @var ExceptionInterface $exception */
        $exception = $this->event->getThrowable();
        $exceptionDefinition = $exception->getExceptionDefinition();

        $request->attributes->set(CustomRequestAttributes::ROUTE_CALLED, true);
        $request->attributes->set(CustomRequestAttributes::RESPONSE_CONTENT_TYPE, MimeType::APPLICATION_XML);
        $request->attributes->set(CustomRequestAttributes::RESPONSE_FORMAT, 'xml');

        $httpResponseStatus = new HttpResponseStatus(HttpResponseStatus::HTTP_CONFLICT);

        $this->exceptionMapperMock
            ->expects(static::at(0))
            ->method('supports')
            ->with($exceptionDefinition)
            ->willReturn(true);

        $this->exceptionMapperMock
            ->expects(static::at(1))
            ->method('map')
            ->with($exceptionDefinition)
            ->willReturn($httpResponseStatus);

        $this->exceptionAdapterMock
            ->expects(static::at(0))
            ->method('supports')
            ->with($exception)
            ->willReturn(false);

        $this->errorListener
            ->registerMapper($this->exceptionMapperMock)
            ->registerAdapter($this->exceptionAdapterMock)
            ->onKernelException($this->event);

        $response = $this->event->getResponse();

        static::assertEquals(HttpResponseStatus::HTTP_CONFLICT, $response->getStatusCode());
    }

    /** @test */
    public function onKernelExceptionWithHtmlRenderingTest(): void
    {
        $this->event->setThrowable(
            new WebserviceException(
                new WebserviceExceptionDefinition(WebserviceExceptionDefinition::CONFLICT)
            )
        );

        $request = $this->event->getRequest();
        /** @var ExceptionInterface $exception */
        $exception = $this->event->getThrowable();
        $exceptionDefinition = $exception->getExceptionDefinition();

        $request->attributes->set(CustomRequestAttributes::ROUTE_CALLED, true);
        $request->attributes->set(CustomRequestAttributes::RESPONSE_CONTENT_TYPE, MimeType::TEXT_HTML);
        $request->attributes->set(CustomRequestAttributes::RESPONSE_FORMAT, 'json');

        $httpResponseStatus = new HttpResponseStatus(HttpResponseStatus::HTTP_CONFLICT);

        $webserviceException = (new ApiExceptionModel\Webservice\WebserviceExceptionApi())
            ->setCode((string) $exception->getCode())
            ->setMessage($exception->getMessage());

        $json = sprintf(
            <<<JSON
            {
                "code": "%s",
                "description": "%s"
            }
            JSON,
            $httpResponseStatus->getCode(),
            $httpResponseStatus->getDescription()
        );

        $html = sprintf(
            <<<HTML
                <html lang="fr">
                    <body>
                        <pre lang="json">
                            {
                                "code": "%s",
                                "description": "%s"
                            }
                        </pre>
                    </body>
                </html>
            HTML,
            $httpResponseStatus->getCode(),
            $httpResponseStatus->getDescription()
        );

        $this->exceptionMapperMock
            ->expects(static::at(0))
            ->method('supports')
            ->with($exceptionDefinition)
            ->willReturn(true);

        $this->exceptionMapperMock
            ->expects(static::at(1))
            ->method('map')
            ->with($exceptionDefinition)
            ->willReturn($httpResponseStatus);

        $this->exceptionAdapterMock
            ->expects(static::at(0))
            ->method('supports')
            ->with($exception)
            ->willReturn(true);

        $this->exceptionAdapterMock
            ->expects(static::at(1))
            ->method('adaptBusinessExceptionToApiException')
            ->with($exception)
            ->willReturn($webserviceException);

        $this->serializerMock
            ->expects(static::once())
            ->method('serialize')
            ->with($webserviceException, 'json')
            ->willReturn($json);

        $this->twigMock
            ->expects(static::once())
            ->method('render')
            ->with(
                '@ItspireFrameworkExtra/response.html.twig',
                [
                    'controllerResult' => $json,
                    'format' => 'json',
                ]
            )
            ->willReturn($html);

        $this->errorListener
            ->registerMapper($this->exceptionMapperMock)
            ->registerAdapter($this->exceptionAdapterMock)
            ->onKernelException($this->event);

        $response = $this->event->getResponse();

        static::assertEquals(HttpResponseStatus::HTTP_CONFLICT, $response->getStatusCode());
        static::assertEquals($html, $response->getContent());
    }
}
