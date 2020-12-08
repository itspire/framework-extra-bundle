<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Util\Strategy\Annotation\Processor;

use Itspire\Exception\Http\Definition\HttpExceptionDefinition;
use Itspire\Exception\Http\HttpException;
use Itspire\FrameworkExtraBundle\Annotation\AnnotationInterface;
use Itspire\FrameworkExtraBundle\Annotation\Consumes;
use Itspire\FrameworkExtraBundle\Configuration\CustomRequestAttributes;
use Itspire\FrameworkExtraBundle\Util\MimeTypeMatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class ConsumesProcessor extends AbstractAnnotationProcessor
{
    private ?MimeTypeMatcherInterface $mimeTypeMatcher = null;

    public function __construct(LoggerInterface $logger, MimeTypeMatcherInterface $mimeTypeMatcher)
    {
        parent::__construct($logger);

        $this->mimeTypeMatcher = $mimeTypeMatcher;
    }

    public function supports(AnnotationInterface $annotation): bool
    {
        return $annotation instanceof Consumes;
    }

    protected function handleProcess(ControllerEvent $event, AnnotationInterface $annotation): void
    {
        $request = $event->getRequest();

        $this->checkAlreadyProcessed(
            $request,
            $event->getController(),
            CustomRequestAttributes::CONSUMES_ANNOTATION_PROCESSED,
            $this->getAnnotationName($annotation)
        );

        $match = $this->mimeTypeMatcher->findMimeTypeMatch(
            [$request->headers->get('Content-Type')],
            $annotation->getConsumableContentTypes()
        );

        if (null === $match) {
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

        if (!empty($annotation->getDeserializationGroups())) {
            $request->attributes->set(
                CustomRequestAttributes::REQUEST_DESERIALIZATION_GROUPS,
                $annotation->getDeserializationGroups()
            );
        }

        $request->attributes->set(CustomRequestAttributes::CONSUMES_ANNOTATION_PROCESSED, true);
    }
}
