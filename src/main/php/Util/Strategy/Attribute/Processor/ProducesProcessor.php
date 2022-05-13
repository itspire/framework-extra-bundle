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
use Itspire\FrameworkExtraBundle\Attribute\Produces;
use Itspire\FrameworkExtraBundle\Configuration\CustomRequestAttributes;
use Itspire\FrameworkExtraBundle\Util\MimeTypeMatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class ProducesProcessor extends AbstractAttributeProcessor implements AttributeProcessorInterface
{
    public function __construct(
        private MimeTypeMatcherInterface $mimeTypeMatcher,
        private bool $allowHTMLResponseContentType,
        LoggerInterface $logger
    ) {
        parent::__construct($logger);
    }

    public function supports(AttributeInterface $attribute): bool
    {
        return $attribute instanceof Produces;
    }

    protected function handleProcess(ControllerEvent $event, AttributeInterface $attribute): void
    {
        $request = $event->getRequest();

        $this->checkAlreadyProcessed(
            $attribute,
            $request,
            $event->getController(),
            CustomRequestAttributes::PRODUCES_PROCESSED
        );

        $match = $this->mimeTypeMatcher->findMimeTypeMatch(
            $request->getAcceptableContentTypes(),
            $attribute->getAcceptableFormats()
        );

        if (null === $match) {
            $this->logger->alert(
                vsprintf(
                    format: 'Unsupported Media Type(s) used for acceptable response content type in route %s (%s).',
                    values: [
                        $request->attributes->get(key: '_route'),
                        implode(separator: ', ', array: $request->getAcceptableContentTypes()),
                    ]
                )
            );

            throw new HttpException(HttpExceptionDefinition::HTTP_NOT_ACCEPTABLE);
        }

        $contentType = (
            MimeType::TEXT_HTML->value !== $match
            && in_array(
                needle: $request->query->get(key: 'renderHtml', default: false),
                haystack: ['true', true, '1', 1],
                strict: true
            )
            && true === $this->allowHTMLResponseContentType
        ) ? MimeType::TEXT_HTML->value : $match;

        $request->attributes->add([
            CustomRequestAttributes::RESPONSE_CONTENT_TYPE => $contentType,
            CustomRequestAttributes::RESPONSE_FORMAT => $request->getFormat(
                (MimeType::TEXT_HTML->value === $match) ? MimeType::APPLICATION_JSON->value : $match
            ),
            CustomRequestAttributes::RESPONSE_SERIALIZATION_GROUPS => $attribute->getSerializationGroups(),
            CustomRequestAttributes::PRODUCES_PROCESSED => true
        ]);
    }
}
