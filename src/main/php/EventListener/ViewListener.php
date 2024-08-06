<?php

/*
 * Copyright (c) 2016 - 2024 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\EventListener;

use Itspire\Common\Enum\Http\HttpMethod;
use Itspire\Common\Enum\Http\HttpResponseStatus;
use Itspire\Common\Enum\MimeType;
use Itspire\Exception\Definition\Http\HttpExceptionDefinition;
use Itspire\Exception\Http\HttpException;
use Itspire\FrameworkExtraBundle\Configuration\CustomRequestAttributes;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Twig\Environment;

class ViewListener extends AbstractTemplateRendererListener
{
    private const HANDLED_MIME_TYPES = [MimeType::TEXT_HTML, MimeType::APPLICATION_XML, MimeType::APPLICATION_JSON];

    public function __construct(
        private readonly SerializerInterface $serializer,
        LoggerInterface $logger,
        Environment $twig
    ) {
        parent::__construct($logger, twig: $twig);
    }

    public function onKernelView(ViewEvent $event): void
    {
        $request = $event->getRequest();
        $controllerResult = $event->getControllerResult();

        // When @Template is used, even the RESPONSE_STATUS_CODE attribute will not be taken into account
        if (
            true === $request->attributes->get(key: CustomRequestAttributes::ROUTE_CALLED)
            && !$request->attributes->has(key: '_template')
        ) {
            $responseStatusCode = $request->attributes->get(
                key: CustomRequestAttributes::RESPONSE_STATUS_CODE,
                default: $this->getResponseStatus($request)->value
            );

            if ($controllerResult instanceof File) {
                $event->setResponse(
                    new BinaryFileResponse(
                        file: $controllerResult,
                        status: $responseStatusCode,
                        contentDisposition: ResponseHeaderBag::DISPOSITION_INLINE
                    )
                );
            } else {
                $response = new Response(status: $responseStatusCode);

                if (null !== $controllerResult) {
                    $responseContentType = $request->attributes->get(
                        key: CustomRequestAttributes::RESPONSE_CONTENT_TYPE
                    );

                    if (
                        null !== $responseContentType
                        && in_array(
                            needle: $responseContentType,
                            haystack: array_map(
                                static fn(MimeType $mimeType) => $mimeType->value,
                                self::HANDLED_MIME_TYPES
                            ),
                            strict: true
                        )
                    ) {
                        $messagePart = is_array($controllerResult)
                            ? 'array'
                            : 'object of type ' . get_class($event->getControllerResult());

                        $responseFormat = $request->attributes->get(key: CustomRequestAttributes::RESPONSE_FORMAT);

                        $response->headers->set(key: 'Content-Type', values: $responseContentType);

                        $serializedContent = $this->serializeControllerResult($event, $responseFormat, $messagePart);

                        $response->setContent(
                            ($responseContentType === MimeType::TEXT_HTML->value)
                                ? $this->renderTemplate($responseFormat, $serializedContent, $messagePart)
                                : $serializedContent
                        );
                    }
                }

                $event->setResponse($response);
            }
        }
    }

    private function serializeControllerResult(
        ViewEvent $event,
        string $responseFormat,
        string $errorMessagePart
    ): string {
        $request = $event->getRequest();
        $controllerResult = $event->getControllerResult();

        $serializationContext = SerializationContext::create();
        if (false !== $request->attributes->has(key: CustomRequestAttributes::RESPONSE_SERIALIZATION_GROUPS)) {
            $serializationContext->setGroups(
                $request->attributes->get(key: CustomRequestAttributes::RESPONSE_SERIALIZATION_GROUPS)
            );
        }

        try {
            return $this->serializer->serialize($controllerResult, $responseFormat, $serializationContext);
        } catch (\Exception $serializerException) {
            $this->logger->error(
                vsprintf(format: 'Could not serialize response content from %s', values: [$errorMessagePart]),
                ['exception' => $serializerException]
            );

            throw new HttpException(HttpExceptionDefinition::HTTP_INTERNAL_SERVER_ERROR, $serializerException);
        }
    }

    private function getResponseStatus(Request $request): HttpResponseStatus
    {
        return match (HttpMethod::tryFrom($request->getMethod())) {
            HttpMethod::POST => HttpResponseStatus::HTTP_CREATED,
            HttpMethod::DELETE => HttpResponseStatus::HTTP_NO_CONTENT,
            default => HttpResponseStatus::HTTP_OK
        };
    }
}
