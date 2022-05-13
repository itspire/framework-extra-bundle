<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\EventListener;

use Itspire\Exception\Definition\Http\HttpExceptionDefinition;
use Itspire\Exception\Http\HttpException;
use Psr\Log\LoggerInterface;
use Twig\Environment;

abstract class AbstractTemplateRendererListener
{
    public function __construct(protected LoggerInterface $logger, private Environment $twig)
    {
    }

    protected function renderTemplate(
        string $responseFormat,
        string $serializedContent,
        string $errorMessagePart
    ): string {
        try {
            return $this->twig->render(
                '@ItspireFrameworkExtra/response.html.twig',
                ['controllerResult' => $serializedContent, 'format' => $responseFormat]
            );
        } catch (\Throwable $renderException) {
            $this->logger->error(
                vsprintf(format: 'Could not render template with %s', values: [$errorMessagePart]),
                ['exception' => $renderException]
            );

            throw new HttpException(HttpExceptionDefinition::HTTP_INTERNAL_SERVER_ERROR, $renderException);
        }
    }
}
