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
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

abstract class AbstractAnnotationProcessor implements AnnotationProcessorInterface
{
    protected ?LoggerInterface $logger = null;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function process(ControllerEvent $event, AnnotationInterface $annotation): void
    {
        if (false === $this->supports($annotation)) {
            $this->logger->error(
                sprintf('Unsupported Annotation %s called %s::process.', get_class($annotation), static::class)
            );

            throw new HttpException(
                new HttpExceptionDefinition(HttpExceptionDefinition::HTTP_INTERNAL_SERVER_ERROR)
            );
        }

        $this->handleProcess($event, $annotation);
    }

    abstract protected function handleProcess(ControllerEvent $event, AnnotationInterface $annotation): void;

    protected function checkAlreadyProcessed(
        Request $request,
        callable $controller,
        string $attributeKey,
        string $annotationName
    ): void {
        /** @noinspection PhpUnhandledExceptionInspection */
        $reflectionClass = new \ReflectionClass(get_class($controller[0]));

        if (true === $request->attributes->has($attributeKey)) {
            $this->logger->error(
                sprintf(
                    'Duplicate @%s annotation found on %s::%s.',
                    $annotationName,
                    $reflectionClass->getName(),
                    $controller[1]
                )
            );

            throw new HttpException(
                new HttpExceptionDefinition(HttpExceptionDefinition::HTTP_INTERNAL_SERVER_ERROR)
            );
        }
    }

    protected function getAnnotationName(AnnotationInterface $annotation): string
    {
        return substr(strrchr(get_class($annotation), '\\'), 1);
    }
}
