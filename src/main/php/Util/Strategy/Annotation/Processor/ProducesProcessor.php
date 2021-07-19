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
use Itspire\FrameworkExtraBundle\Annotation\Produces;
use Itspire\FrameworkExtraBundle\Configuration\CustomRequestAttributes;
use Itspire\FrameworkExtraBundle\Util\MimeTypeMatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class ProducesProcessor extends AbstractAnnotationProcessor
{
    private ?MimeTypeMatcherInterface $mimeTypeMatcher = null;
    private bool $allowHTMLResponseContentType;

    public function __construct(
        LoggerInterface $logger,
        MimeTypeMatcherInterface $mimeTypeMatcher,
        bool $allowHTMLResponseContentType
    ) {
        parent::__construct($logger);

        $this->mimeTypeMatcher = $mimeTypeMatcher;
        $this->allowHTMLResponseContentType = $allowHTMLResponseContentType;
    }

    public function supports(AnnotationInterface $annotation): bool
    {
        return $annotation instanceof Produces;
    }

    protected function handleProcess(ControllerEvent $event, AnnotationInterface $annotation): void
    {
        $request = $event->getRequest();

        $this->checkAlreadyProcessed(
            $request,
            $event->getController(),
            CustomRequestAttributes::PRODUCES_ANNOTATION_PROCESSED,
            $this->getAnnotationName($annotation)
        );

        $match = $this->mimeTypeMatcher->findMimeTypeMatch(
            $request->getAcceptableContentTypes(),
            $annotation->getAcceptableFormats()
        );

        if (null === $match) {
            $this->logger->alert(
                sprintf(
                    'Unsupported Media Type(s) used for acceptable response content type in route %s (%s).',
                    $request->attributes->get('_route'),
                    implode(', ', $request->getAcceptableContentTypes())
                )
            );

            throw new HttpException(
                new HttpExceptionDefinition(HttpExceptionDefinition::HTTP_NOT_ACCEPTABLE)
            );
        }

        $contentType = (
            MimeType::TEXT_HTML !== $match
            && in_array($request->query->get('renderHtml', false), ['true', true, '1', 1], true)
            && true === $this->allowHTMLResponseContentType
        ) ? MimeType::TEXT_HTML : $match;

        $request->attributes->set(CustomRequestAttributes::RESPONSE_CONTENT_TYPE, $contentType);
        $request->attributes->set(
            CustomRequestAttributes::RESPONSE_FORMAT,
            $request->getFormat((MimeType::TEXT_HTML === $match) ? MimeType::APPLICATION_JSON : $match)
        );

        $request->attributes->set(
            CustomRequestAttributes::RESPONSE_SERIALIZATION_GROUPS,
            $annotation->getSerializationGroups()
        );

        $request->attributes->set(CustomRequestAttributes::PRODUCES_ANNOTATION_PROCESSED, true);
    }
}
