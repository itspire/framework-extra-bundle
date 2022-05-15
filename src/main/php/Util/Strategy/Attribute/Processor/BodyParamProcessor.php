<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Util\Strategy\Attribute\Processor;

use Itspire\Common\Enum\MimeType;
use Itspire\Exception\Definition\Http\HttpExceptionDefinition;
use Itspire\Exception\Http\HttpException;
use Itspire\FrameworkExtraBundle\Attribute\AttributeInterface;
use Itspire\FrameworkExtraBundle\Attribute\BodyParam;
use Itspire\FrameworkExtraBundle\Attribute\ParamAttributeInterface;
use Itspire\FrameworkExtraBundle\Configuration\CustomRequestAttributes;
use Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck\TypeCheckHandlerInterface;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class BodyParamProcessor extends AbstractParamAttributeProcessor
{
    private const SUPPORTED_CONTENT_TYPES = [MimeType::APPLICATION_XML, MimeType::APPLICATION_JSON];

    public function __construct(
        private SerializerInterface $serializer,
        LoggerInterface $logger,
        TypeCheckHandlerInterface $typeCheckHandler
    ) {
        parent::__construct($typeCheckHandler, $logger);
    }

    public function supports(AttributeInterface $attribute): bool
    {
        return $attribute instanceof BodyParam;
    }

    /** @param ParamAttributeInterface $attribute */
    protected function handleProcess(ControllerEvent $event, AttributeInterface $attribute): void
    {
        $request = $event->getRequest();

        $this->checkAlreadyProcessed(
            $attribute,
            $request,
            $event->getController(),
            CustomRequestAttributes::BODYPARAM_PROCESSED
        );

        parent::handleProcess($event, $attribute);

        $request->attributes->set(key: CustomRequestAttributes::BODYPARAM_PROCESSED, value: true);
    }

    /** @param BodyParam $attribute */
    protected function getParamValue(Request $request, ParamAttributeInterface $attribute): mixed
    {
        $paramValue = parent::getParamValue($request, $attribute);

        if (empty($paramValue)) {
            return $paramValue;
        }

        if (null !== $attribute->getClass()) {
            if (
                !in_array(
                    $request->headers->get(key: 'Content-Type'),
                    array_map(
                        fn (MimeType $supportedContentType): string => $supportedContentType->value,
                        self::SUPPORTED_CONTENT_TYPES
                    ),
                    true
                )
            ) {
                $this->logger->alert(
                    vsprintf(
                        format: 'Unsupported Media Type "%s" used for body content in route "%s".',
                        values: [$request->headers->get(key: 'Content-Type'), $request->attributes->get(key: '_route')]
                    )
                );

                throw new HttpException(HttpExceptionDefinition::HTTP_UNSUPPORTED_MEDIA_TYPE);
            }

            $deserializationContext = DeserializationContext::create();
            if (false !== $request->attributes->has(key: CustomRequestAttributes::REQUEST_DESERIALIZATION_GROUPS)) {
                $deserializationContext->setGroups(
                    $request->attributes->get(key: CustomRequestAttributes::REQUEST_DESERIALIZATION_GROUPS)
                );
            }

            try {
                $paramValue = $this->serializer->deserialize(
                    $paramValue,
                    $attribute->getClass(),
                    $request->getContentType(),
                    $deserializationContext
                );
            } catch (\Throwable $exception) {
                $this->logger->alert(
                    vsprintf(
                        format: 'Deserialization to parameter "%s" of type "%s" failed.',
                        values: [$attribute->getName(), $attribute->getType()]
                    ),
                    ['exception' => $exception]
                );

                throw new HttpException(HttpExceptionDefinition::HTTP_BAD_REQUEST, $exception);
            }
        }

        return $paramValue;
    }
}
