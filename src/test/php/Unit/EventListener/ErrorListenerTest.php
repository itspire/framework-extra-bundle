<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Unit\EventListener;

use Itspire\Common\Enum\MimeType;
use Itspire\Exception\Http\Definition\HttpExceptionDefinition;
use Itspire\Exception\Http\HttpException;
use Itspire\Exception\Resolver\ExceptionResolverInterface;
use Itspire\FrameworkExtraBundle\Configuration\CustomRequestAttributes;
use Itspire\FrameworkExtraBundle\EventListener\ErrorListener;
use Itspire\Http\Common\Enum\HttpResponseStatus;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Stream;
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
    private ?MockObject $exceptionResolverMock = null;
    private ?ErrorListener $errorListener = null;
    private ?ExceptionEvent $event = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->twigMock = $this->getMockBuilder(Environment::class)->disableOriginalConstructor()->getMock();
        $this->exceptionResolverMock = $this->getMockBuilder(ExceptionResolverInterface::class)->getMock();

        $this->event = new ExceptionEvent(
            $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            new HttpException(
                new HttpExceptionDefinition(HttpExceptionDefinition::HTTP_CONFLICT)
            )
        );

        $this->errorListener = new ErrorListener($this->loggerMock, $this->twigMock);
    }

    protected function tearDown(): void
    {
        unset($this->errorListener, $this->loggerMock, $this->twigMock);

        parent::tearDown();
    }

    /** @test */
    public function registerResolverTest(): void
    {
        $reflectionClass = new \ReflectionClass(ErrorListener::class);
        $reflectionProperty = $reflectionClass->getProperty('exceptionResolvers');
        $reflectionProperty->setAccessible(true);

        static::assertCount(0, $reflectionProperty->getValue($this->errorListener));

        $this->errorListener->registerResolver($this->exceptionResolverMock);
        static::assertCount(1, $reflectionProperty->getValue($this->errorListener));

        $this->errorListener->registerResolver($this->exceptionResolverMock);
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
    public function onKernelExceptionNoResolverTest(): void
    {
        $request = $this->event->getRequest();
        $exception = $this->event->getThrowable();

        $request->attributes->set(CustomRequestAttributes::ROUTE_CALLED, true);
        $request->attributes->set(CustomRequestAttributes::RESPONSE_CONTENT_TYPE, MimeType::APPLICATION_XML);
        $request->attributes->set(CustomRequestAttributes::RESPONSE_FORMAT, 'xml');

        $this->loggerMock
            ->expects(static::once())
            ->method('critical')
            ->with(
                sprintf(
                    'Unresolved %s exception : %d - %s.',
                    get_class($exception),
                    $exception->getCode(),
                    $exception->getMessage()
                ),
                ['exception' => $exception]
            );

        $this->errorListener->onKernelException($this->event);

        $response = $this->event->getResponse();

        static::assertEquals(HttpResponseStatus::HTTP_INTERNAL_SERVER_ERROR[0], $response->getStatusCode());
    }

    /** @test */
    public function onKernelExceptionNoResolverForExceptionTest(): void
    {
        $request = $this->event->getRequest();
        $exception = $this->event->getThrowable();

        $request->attributes->set(CustomRequestAttributes::ROUTE_CALLED, true);
        $request->attributes->set(CustomRequestAttributes::RESPONSE_CONTENT_TYPE, MimeType::APPLICATION_XML);
        $request->attributes->set(CustomRequestAttributes::RESPONSE_FORMAT, 'xml');

        $this->exceptionResolverMock->expects(static::once())->method('supports')->with($exception)->willReturn(false);

        $this->loggerMock
            ->expects(static::once())
            ->method('critical')
            ->with(
                sprintf(
                    'Unresolved %s exception : %d - %s.',
                    get_class($exception),
                    $exception->getCode(),
                    $exception->getMessage()
                ),
                ['exception' => $exception]
            );

        $this->errorListener
            ->registerResolver($this->exceptionResolverMock)
            ->onKernelException($this->event);

        $response = $this->event->getResponse();

        static::assertEquals(HttpResponseStatus::HTTP_INTERNAL_SERVER_ERROR[0], $response->getStatusCode());
    }

    public function htmlRenderProvider(): array
    {
        return [
            'json' => [
                sprintf(
                    <<<JSON
                    {
                        "code": "%s",
                        "description": "%s"
                    }
                    JSON,
                    HttpExceptionDefinition::HTTP_FORBIDDEN[0],
                    HttpExceptionDefinition::HTTP_FORBIDDEN[1]
                )
            ],
            'empty' => [''],
        ];
    }

    /**
     * @test
     * @dataProvider htmlRenderProvider
     */
    public function onKernelExceptionHtmlRenderingErrorTest(string $content): void
    {
        $request = $this->event->getRequest();
        $exception = $this->event->getThrowable();

        $request->attributes->set(CustomRequestAttributes::ROUTE_CALLED, true);
        $request->attributes->set(CustomRequestAttributes::RESPONSE_CONTENT_TYPE, MimeType::TEXT_HTML);
        $request->attributes->set(CustomRequestAttributes::RESPONSE_FORMAT, 'json');

        $response = (new Response())
            ->withStatus(HttpResponseStatus::HTTP_FORBIDDEN[0], HttpResponseStatus::HTTP_FORBIDDEN[1])
            ->withBody(Stream::create($content));

        $this->exceptionResolverMock->expects(static::once())->method('supports')->with($exception)->willReturn(true);
        $this->exceptionResolverMock
            ->expects(static::once())
            ->method('resolve')
            ->with($exception, 'json')
            ->willReturn($response);

        $this->twigMock
            ->expects(static::once())
            ->method('render')
            ->with(
                '@ItspireFrameworkExtra/response.html.twig',
                [
                    'controllerResult' => !empty($content)
                        ? (string) $response->getBody()
                        : $content,
                    'format' => 'json',
                ]
            )
            ->willThrowException(new \Exception());

        $this->errorListener
            ->registerResolver($this->exceptionResolverMock)
            ->onKernelException($this->event);

        $response = $this->event->getResponse();

        static::assertEquals(HttpResponseStatus::HTTP_INTERNAL_SERVER_ERROR[0], $response->getStatusCode());
    }

    /** @test */
    public function onKernelExceptionNoHtmlRenderingTest(): void
    {
        $request = $this->event->getRequest();
        $exception = $this->event->getThrowable();

        $request->attributes->set(CustomRequestAttributes::ROUTE_CALLED, true);
        $request->attributes->set(CustomRequestAttributes::RESPONSE_CONTENT_TYPE, MimeType::APPLICATION_XML);
        $request->attributes->set(CustomRequestAttributes::RESPONSE_FORMAT, 'xml');

        $response = (new Response())
            ->withStatus(HttpResponseStatus::HTTP_FORBIDDEN[0], HttpResponseStatus::HTTP_FORBIDDEN[1]);

        $this->exceptionResolverMock->expects(static::once())->method('supports')->with($exception)->willReturn(true);
        $this->exceptionResolverMock
            ->expects(static::once())
            ->method('resolve')
            ->with($exception, 'xml')
            ->willReturn($response);

        $this->errorListener
            ->registerResolver($this->exceptionResolverMock)
            ->onKernelException($this->event);

        $response = $this->event->getResponse();

        static::assertEquals(HttpResponseStatus::HTTP_FORBIDDEN[0], $response->getStatusCode());
    }

    /** @test */
    public function onKernelExceptionWithHtmlRenderingTest(): void
    {
        $request = $this->event->getRequest();
        $exception = $this->event->getThrowable();

        $request->attributes->set(CustomRequestAttributes::ROUTE_CALLED, true);
        $request->attributes->set(CustomRequestAttributes::RESPONSE_CONTENT_TYPE, MimeType::TEXT_HTML);
        $request->attributes->set(CustomRequestAttributes::RESPONSE_FORMAT, 'json');

        $json = sprintf(
            <<<JSON
            {
                "code": "%s",
                "description": "%s"
            }
            JSON,
            HttpExceptionDefinition::HTTP_FORBIDDEN[0],
            HttpExceptionDefinition::HTTP_FORBIDDEN[1]
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
            HttpResponseStatus::HTTP_FORBIDDEN[0],
            HttpResponseStatus::HTTP_FORBIDDEN[1]
        );

        $response = new Response();
        $response = $response
            ->withStatus(HttpResponseStatus::HTTP_FORBIDDEN[0], HttpResponseStatus::HTTP_FORBIDDEN[1])
            ->withBody(Stream::create($json));

        $this->exceptionResolverMock->expects(static::once())->method('supports')->with($exception)->willReturn(true);
        $this->exceptionResolverMock
            ->expects(static::once())
            ->method('resolve')
            ->with($exception, 'json')
            ->willReturn($response);

        $this->twigMock
            ->expects(static::once())
            ->method('render')
            ->with(
                '@ItspireFrameworkExtra/response.html.twig',
                [
                    'controllerResult' => (string) $response->getBody(),
                    'format' => 'json',
                ]
            )
            ->willReturn($html);

        $this->errorListener
            ->registerResolver($this->exceptionResolverMock)
            ->onKernelException($this->event);

        $response = $this->event->getResponse();

        static::assertEquals(HttpResponseStatus::HTTP_FORBIDDEN[0], $response->getStatusCode());
        static::assertEquals($html, $response->getContent());
    }
}
