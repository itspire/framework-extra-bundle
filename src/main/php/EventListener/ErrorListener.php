<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\EventListener;

use Itspire\Common\Enum\MimeType;
use Itspire\Exception\ExceptionInterface;
use Itspire\Exception\Http\HttpException;
use Itspire\Exception\Resolver\ExceptionResolverInterface;
use Itspire\FrameworkExtraBundle\Configuration\CustomRequestAttributes;
use Itspire\Http\Common\Enum\HttpResponseStatus;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Twig\Environment;

class ErrorListener
{
    use TemplateRendererTrait;

    private ?LoggerInterface $logger = null;

    /** @var ExceptionResolverInterface[]  */
    private array $exceptionResolvers = [];

    public function __construct(LoggerInterface $logger, Environment $twig, iterable $exceptionResolvers = [])
    {
        $this->logger = $logger;
        $this->twig = $twig;

        foreach ($exceptionResolvers as $exceptionResolver) {
            $this->registerResolver($exceptionResolver);
        }
    }

    public function registerResolver(ExceptionResolverInterface $resolver): self
    {
        $resolverClass = get_class($resolver);

        if (false === array_key_exists($resolverClass, $this->exceptionResolvers)) {
            $this->exceptionResolvers[$resolverClass] = $resolver;
        }

        return $this;
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        if (true === $request->attributes->get(CustomRequestAttributes::ROUTE_CALLED)) {
            // These are defined if the Produces annotation is defined
            $responseContentType = $request->attributes->get(CustomRequestAttributes::RESPONSE_CONTENT_TYPE);
            $responseFormat = $request->attributes->get(CustomRequestAttributes::RESPONSE_FORMAT);

            if (
                null !== $responseContentType
                && $exception instanceof ExceptionInterface
                && in_array(
                    $responseContentType,
                    [MimeType::TEXT_HTML, MimeType::APPLICATION_XML, MimeType::APPLICATION_JSON],
                    true
                )
            ) {
                $httpResponseStatus = new HttpResponseStatus(HttpResponseStatus::HTTP_INTERNAL_SERVER_ERROR);

                $response = $this->resolveException($exception, $responseFormat);
                $response ??= (new Response())->setStatusCode(
                    (int) $httpResponseStatus->getValue(),
                    $httpResponseStatus->getDescription()
                );

                $response->headers->set('Content-Type', $responseContentType);

                $messagePart = 'exception of type ' . get_class($exception);

                try {
                    $response->setContent(
                        ($responseContentType === MimeType::TEXT_HTML)
                            ? $this->renderTemplate($responseFormat, $response->getContent(), $messagePart)
                            : $response->getContent()
                    );
                } catch (HttpException $httpException) {
                    // Error has already been logged in renderTemplate
                    $response->setStatusCode(
                        (int) $httpException->getExceptionDefinition()->getValue(),
                        $httpException->getExceptionDefinition()->getDescription()
                    );
                }

                $event->setResponse($response);
            }
        }
    }

    private function resolveException(ExceptionInterface $exception, string $responseFormat): ?Response
    {
        foreach ($this->exceptionResolvers as $exceptionResolver) {
            if ($exceptionResolver->supports($exception)) {
                $httpFoundationFactory = new HttpFoundationFactory();

                return $httpFoundationFactory->createResponse(
                    $exceptionResolver->resolve($exception, $responseFormat)
                );
            }
        }

        $this->logger->critical(
            sprintf(
                'Unresolved %s exception : %d - %s.',
                get_class($exception),
                $exception->getCode(),
                $exception->getMessage()
            ),
            ['exception' => $exception]
        );

        return null;
    }
}
