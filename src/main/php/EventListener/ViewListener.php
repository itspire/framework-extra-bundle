<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
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

class ViewListener
{
    use TemplateRendererTrait;

    private const HANDLED_MIME_TYPES = [MimeType::TEXT_HTML, MimeType::APPLICATION_XML, MimeType::APPLICATION_JSON];

    private ?SerializerInterface $serializer = null;
    private ?LoggerInterface $logger = null;

    public function __construct(SerializerInterface $serializer, LoggerInterface $logger, Environment $twig)
    {
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->twig = $twig;
    }

    public function onKernelView(ViewEvent $event): void
    {
        $request = $event->getRequest();
        $controllerResult = $event->getControllerResult();

        // When @Template is used, even the RESPONSE_STATUS_CODE attribute will not be taken into account
        if (
            true === $request->attributes->get(CustomRequestAttributes::ROUTE_CALLED)
            && !$request->attributes->has('_template')
        ) {
            $responseStatusCode = $request->attributes->get(
                CustomRequestAttributes::RESPONSE_STATUS_CODE,
                $this->getResponseStatusCode($request)
            );

            if ($controllerResult instanceof File) {
                $event->setResponse(
                    new BinaryFileResponse(
                        $controllerResult,
                        $responseStatusCode,
                        [],
                        true,
                        ResponseHeaderBag::DISPOSITION_INLINE
                    )
                );
            } else {
                $response = new Response('', $responseStatusCode);

                if (null !== $controllerResult) {
                    // These are defined if the Produces annotation is defined
                    $responseContentType = $request->attributes->get(CustomRequestAttributes::RESPONSE_CONTENT_TYPE);

                    if (
                        null !== $responseContentType
                        && in_array($responseContentType, self::HANDLED_MIME_TYPES, true)
                    ) {
                        $messagePart = (is_array($controllerResult))
                            ? 'array'
                            : 'object of type ' . get_class($event->getControllerResult());

                        $responseFormat = $request->attributes->get(CustomRequestAttributes::RESPONSE_FORMAT);

                        $response->headers->set('Content-Type', $responseContentType);

                        $serializedContent = $this->serializeControllerResult(
                            $event,
                            $responseFormat,
                            $messagePart
                        );

                        $response->setContent(
                            ($responseContentType === MimeType::TEXT_HTML)
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
        if (false !== $request->attributes->has(CustomRequestAttributes::RESPONSE_SERIALIZATION_GROUPS)) {
            $serializationContext->setGroups(
                $request->attributes->get(CustomRequestAttributes::RESPONSE_SERIALIZATION_GROUPS)
            );
        }

        try {
            return $this->serializer->serialize(
                $controllerResult,
                $responseFormat,
                $serializationContext
            );
        } catch (\Exception $serializerException) {
            $this->logger->error(
                sprintf('Could not serialize response content from %s', $errorMessagePart),
                ['exception' => $serializerException]
            );

            throw new HttpException(
                new HttpExceptionDefinition(HttpExceptionDefinition::HTTP_INTERNAL_SERVER_ERROR),
                $serializerException
            );
        }
    }

    private function getResponseStatusCode(Request $request): int
    {
        if (HttpMethod::POST === $request->getMethod()) {
            return HttpResponseStatus::HTTP_CREATED;
        } elseif (HttpMethod::DELETE === $request->getMethod()) {
            return HttpResponseStatus::HTTP_NO_CONTENT;
        }

        return HttpResponseStatus::HTTP_OK;
    }
}
