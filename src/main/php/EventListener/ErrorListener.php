<?php

/*
 * Copyright (c) 2016 - 2024 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\EventListener;

use Itspire\Common\Enum\Http\HttpResponseStatus;
use Itspire\Common\Enum\MimeType;
use Itspire\Exception\Api\Adapter\ExceptionApiAdapterInterface;
use Itspire\Exception\Api\Mapper\ExceptionApiMapperInterface;
use Itspire\Exception\Api\Model as ApiExceptionModel;
use Itspire\Exception\Definition\ExceptionDefinitionInterface;
use Itspire\Exception\ExceptionInterface;
use Itspire\Exception\Http\HttpException;
use Itspire\FrameworkExtraBundle\Configuration\CustomRequestAttributes;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Twig\Environment;

class ErrorListener extends AbstractTemplateRendererListener
{
    /** @var ExceptionApiMapperInterface[]  */
    private array $exceptionApiMappers = [];

    /** @var ExceptionApiAdapterInterface[]  */
    private array $exceptionApiAdapters = [];

    public function __construct(
        private readonly SerializerInterface $serializer,
        LoggerInterface $logger,
        Environment $twig,
        iterable $exceptionApiMappers = [],
        iterable $exceptionApiAdapters = []
    ) {
        parent::__construct($logger, $twig);

        foreach ($exceptionApiMappers as $exceptionApiMapper) {
            $this->registerMapper($exceptionApiMapper);
        }

        foreach ($exceptionApiAdapters as $exceptionApiAdapter) {
            $this->registerAdapter($exceptionApiAdapter);
        }
    }

    public function registerMapper(ExceptionApiMapperInterface $exceptionApiMapper): self
    {
        if (false === array_key_exists($exceptionApiMapper::class, $this->exceptionApiMappers)) {
            $this->exceptionApiMappers[$exceptionApiMapper::class] = $exceptionApiMapper;
        }

        return $this;
    }

    public function registerAdapter(ExceptionApiAdapterInterface $exceptionApiAdapter): self
    {
        if (false === array_key_exists($exceptionApiAdapter::class, $this->exceptionApiAdapters)) {
            $this->exceptionApiAdapters[$exceptionApiAdapter::class] = $exceptionApiAdapter;
        }

        return $this;
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        if (true === $request->attributes->get(key: CustomRequestAttributes::ROUTE_CALLED)) {
            $responseContentType = $request->attributes->get(key: CustomRequestAttributes::RESPONSE_CONTENT_TYPE);
            $responseFormat = $request->attributes->get(key: CustomRequestAttributes::RESPONSE_FORMAT);

            if (
                null !== $responseContentType
                && $exception instanceof ExceptionInterface
                && in_array(
                    $responseContentType,
                    array_map(
                        static fn (MimeType $mimeType) => $mimeType->value,
                        [MimeType::TEXT_HTML, MimeType::APPLICATION_XML, MimeType::APPLICATION_JSON]
                    ),
                    true
                )
            ) {
                $httpResponseStatus = $this->mapException($exception->getExceptionDefinition());
                $apiException = $this->adaptException($exception);

                $response = $responseContentType === MimeType::APPLICATION_JSON->value
                    ? new JsonResponse(status: $httpResponseStatus->value)
                    : new Response(status: $httpResponseStatus->value);

                if (null !== $apiException) {
                    $response->setContent(
                        $this->serializer->serialize($apiException, $responseFormat)
                    );
                }

                $response->headers->set(key: 'Content-Type', values: $responseContentType);
                $messagePart = 'exception type ' . $exception::class;

                try {
                    $response->setContent(
                        ($responseContentType === MimeType::TEXT_HTML->value)
                            ? $this->renderTemplate($responseFormat, $response->getContent(), $messagePart)
                            : $response->getContent()
                    );
                } catch (HttpException $httpException) {
                    // Error has already been logged in renderTemplate
                    $response->setStatusCode(
                        $httpException->getExceptionDefinition()->value,
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
            vsprintf(
                format: 'No HttpResponseStatus mapping found for %s exception definition : %d - %s.',
                values: [
                    $exceptionDefinition::class,
                    $exceptionDefinition->name,
                    $exceptionDefinition->getDescription(),
                ]
            ),
            ['exceptionDefinition' => $exceptionDefinition]
        );

        return HttpResponseStatus::HTTP_INTERNAL_SERVER_ERROR;
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
                vsprintf(
                    format: 'No adapter found for %s exception : %d - %s.',
                    values: [$exception::class, $exception->getCode(), $exception->getMessage()]
                ),
                ['exception' => $exception]
            );
        }

        return null;
    }
}
