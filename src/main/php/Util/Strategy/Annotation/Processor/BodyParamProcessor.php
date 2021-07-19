<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Util\Strategy\Annotation\Processor;

use Itspire\Common\Enum\MimeType;
use Itspire\Exception\Definition\Http\HttpExceptionDefinition;
use Itspire\Exception\Http\HttpException;
use Itspire\FrameworkExtraBundle\Annotation\AnnotationInterface;
use Itspire\FrameworkExtraBundle\Annotation\BodyParam;
use Itspire\FrameworkExtraBundle\Annotation\ParamInterface;
use Itspire\FrameworkExtraBundle\Configuration\CustomRequestAttributes;
use Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck\TypeCheckHandlerInterface;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class BodyParamProcessor extends AbstractParamAnnotationProcessor
{
    private const SUPPORTED_CONTENT_TYPES = [MimeType::APPLICATION_XML, MimeType::APPLICATION_JSON];

    private ?SerializerInterface $serializer = null;

    public function __construct(
        LoggerInterface $logger,
        TypeCheckHandlerInterface $typeCheckHandler,
        SerializerInterface $serializer
    ) {
        parent::__construct($logger, $typeCheckHandler);
        $this->serializer = $serializer;
    }

    public function supports(AnnotationInterface $annotation): bool
    {
        return $annotation instanceof BodyParam;
    }

    /** @param ParamInterface $annotation */
    protected function handleProcess(ControllerEvent $event, AnnotationInterface $annotation): void
    {
        $request = $event->getRequest();

        $this->checkAlreadyProcessed(
            $request,
            $event->getController(),
            CustomRequestAttributes::BODY_PARAM_ANNOTATION_PROCESSED,
            $this->getAnnotationName($annotation)
        );

        parent::handleProcess($event, $annotation);

        $request->attributes->set(CustomRequestAttributes::BODY_PARAM_ANNOTATION_PROCESSED, true);
    }

    /** @param BodyParam $annotation */
    protected function getParamValue(Request $request, ParamInterface $annotation)
    {
        $paramValue = parent::getParamValue($request, $annotation);

        // If request content type is supported by the serializer
        if (empty($paramValue)) {
            return $paramValue;
        }

        if (null !== $annotation->getClass()) {
            if (!in_array($request->headers->get('Content-Type'), self::SUPPORTED_CONTENT_TYPES, true)) {
                $this->logger->alert(
                    sprintf(
                        'Unsupported Media Type %s used for body content in route %s.',
                        $request->headers->get('Content-Type'),
                        $request->attributes->get('_route')
                    )
                );

                throw new HttpException(
                    new HttpExceptionDefinition(HttpExceptionDefinition::HTTP_UNSUPPORTED_MEDIA_TYPE)
                );
            }

            $deserializationContext = DeserializationContext::create();
            if (false !== $request->attributes->has(CustomRequestAttributes::REQUEST_DESERIALIZATION_GROUPS)) {
                $deserializationContext->setGroups(
                    $request->attributes->get(CustomRequestAttributes::REQUEST_DESERIALIZATION_GROUPS)
                );
            }

            try {
                $paramValue = $this->serializer->deserialize(
                    $paramValue,
                    $annotation->getClass(),
                    $request->getContentType(),
                    $deserializationContext
                );
            } catch (\Throwable $exception) {
                $this->logger->alert(
                    sprintf(
                        'Deserialization to parameter %s of type %s failed.',
                        $annotation->getName(),
                        $annotation->getType()
                    ),
                    ['exception' => $exception]
                );

                throw new HttpException(
                    new HttpExceptionDefinition(HttpExceptionDefinition::HTTP_BAD_REQUEST),
                    $exception
                );
            }
        }

        return $paramValue;
    }
}
