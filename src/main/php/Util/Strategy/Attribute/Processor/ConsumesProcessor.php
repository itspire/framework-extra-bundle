<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Util\Strategy\Attribute\Processor;

use Itspire\Exception\Definition\Http\HttpExceptionDefinition;
use Itspire\Exception\Http\HttpException;
use Itspire\FrameworkExtraBundle\Attribute\AttributeInterface;
use Itspire\FrameworkExtraBundle\Attribute\Consumes;
use Itspire\FrameworkExtraBundle\Configuration\CustomRequestAttributes;
use Itspire\FrameworkExtraBundle\Util\MimeTypeMatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class ConsumesProcessor extends AbstractAttributeProcessor
{
    public function __construct(private MimeTypeMatcherInterface $mimeTypeMatcher, LoggerInterface $logger)
    {
        parent::__construct($logger);
    }

    public function supports(AttributeInterface $attribute): bool
    {
        return $attribute instanceof Consumes;
    }

    protected function handleProcess(ControllerEvent $event, AttributeInterface $attribute): void
    {
        $request = $event->getRequest();

        $this->checkAlreadyProcessed(
            $attribute,
            $request,
            $event->getController(),
            CustomRequestAttributes::CONSUMES_PROCESSED
        );

        $match = $this->mimeTypeMatcher->findMimeTypeMatch(
            [$request->headers->get(key: 'Content-Type')],
            $attribute->getConsumableContentTypes()
        );

        if (null === $match) {
            $this->logger->alert(
                vsprintf(
                    format: 'Unsupported Media Type %s used for body content in route %s.',
                    values: [$request->headers->get(key: 'Content-Type'), $request->attributes->get(key: '_route')]
                )
            );

            throw new HttpException(HttpExceptionDefinition::HTTP_UNSUPPORTED_MEDIA_TYPE);
        }

        if (!empty($attribute->getDeserializationGroups())) {
            $request->attributes->set(
                key: CustomRequestAttributes::REQUEST_DESERIALIZATION_GROUPS,
                value: $attribute->getDeserializationGroups()
            );
        }

        $request->attributes->set(key: CustomRequestAttributes::CONSUMES_PROCESSED, value: true);
    }
}
