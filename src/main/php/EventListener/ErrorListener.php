<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\EventListener;

use Itspire\Common\Enum\Http\HttpResponseStatus;
use Itspire\Common\Enum\MimeType;
use Itspire\Exception\Api\Model as ApiExceptionModel;
use Itspire\Exception\Api\Adapter\ExceptionApiAdapterInterface;
use Itspire\Exception\Api\Mapper\ExceptionApiMapperInterface;
use Itspire\Exception\Definition\ExceptionDefinitionInterface;
use Itspire\Exception\ExceptionInterface;
use Itspire\Exception\Http\HttpException;
use Itspire\FrameworkExtraBundle\Configuration\CustomRequestAttributes;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Twig\Environment;

class ErrorListener
{
    use TemplateRendererTrait;

    private ?LoggerInterface $logger = null;
    private ?Environment $twig = null;
    private ?SerializerInterface $serializer = null;

    /** @var ExceptionApiMapperInterface[]  */
    private array $exceptionApiMappers = [];

    /** @var ExceptionApiAdapterInterface[]  */
    private array $exceptionApiAdapters = [];

    public function __construct(
        LoggerInterface $logger,
        Environment $twig,
        SerializerInterface $serializer,
        iterable $exceptionApiMappers = [],
        iterable $exceptionApiAdapters = []
    ) {
        $this->logger = $logger;
        $this->twig = $twig;
        $this->serializer = $serializer;

        foreach ($exceptionApiMappers as $exceptionApiMapper) {
            $this->registerMapper($exceptionApiMapper);
        }

        foreach ($exceptionApiAdapters as $exceptionApiAdapter) {
            $this->registerAdapter($exceptionApiAdapter);
        }
    }

    public function registerMapper(ExceptionApiMapperInterface $exceptionApiMapper): self
    {
        $mapperClass = get_class($exceptionApiMapper);

        if (false === array_key_exists($mapperClass, $this->exceptionApiMappers)) {
            $this->exceptionApiMappers[$mapperClass] = $exceptionApiMapper;
        }

        return $this;
    }

    public function registerAdapter(ExceptionApiAdapterInterface $exceptionApiAdapter): self
    {
        $adapterClass = get_class($exceptionApiAdapter);

        if (false === array_key_exists($adapterClass, $this->exceptionApiAdapters)) {
            $this->exceptionApiAdapters[$adapterClass] = $exceptionApiAdapter;
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
                $httpResponseStatus = $this->mapException($exception->getExceptionDefinition());
                $apiException = $this->adaptException($exception);

                $response = new Response('', $httpResponseStatus->getValue());

                if (null !== $apiException) {
                    $response->setContent(
                        $this->serializer->serialize($apiException, $responseFormat)
                    );
                }

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

    private function mapException(ExceptionDefinitionInterface $exceptionDefinition): HttpResponseStatus
    {
        foreach ($this->exceptionApiMappers as $exceptionApiMapper) {
            if ($exceptionApiMapper->supports($exceptionDefinition)) {
                return $exceptionApiMapper->map($exceptionDefinition);
            }
        }

        $this->logger->notice(
            sprintf(
                'No HttpResponseStatus mapping found for %s exception definition : %d - %s.',
                get_class($exceptionDefinition),
                $exceptionDefinition->getCode(),
                $exceptionDefinition->getDescription()
            ),
            ['exceptionDefinition' => $exceptionDefinition]
        );

        return new HttpResponseStatus(HttpResponseStatus::HTTP_INTERNAL_SERVER_ERROR);
    }

    private function adaptException(ExceptionInterface $exception): ?ApiExceptionModel\ExceptionApiInterface
    {
        foreach ($this->exceptionApiAdapters as $exceptionApiAdapter) {
            if ($exceptionApiAdapter->supports($exception)) {
                return $exceptionApiAdapter->adaptBusinessExceptionToApiException($exception);
            }
        }

        // Http exceptions do not require an adapter
        if (!$exception instanceof HttpException) {
            $this->logger->notice(
                sprintf(
                    'No adapter found for %s exception : %d - %s.',
                    get_class($exception),
                    $exception->getCode(),
                    $exception->getMessage()
                ),
                ['exception' => $exception]
            );
        }

        return null;
    }
}
