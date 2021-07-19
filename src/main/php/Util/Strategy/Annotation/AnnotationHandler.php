<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Util\Strategy\Annotation;

use Itspire\Exception\Definition\Http\HttpExceptionDefinition;
use Itspire\Exception\Http\HttpException;
use Itspire\FrameworkExtraBundle\Annotation\AnnotationInterface;
use Itspire\FrameworkExtraBundle\Util\Strategy\Annotation\Processor\AnnotationProcessorInterface;
use Itspire\FrameworkExtraBundle\Util\Strategy\Annotation\Processor\BodyParamProcessor;
use Itspire\FrameworkExtraBundle\Util\Strategy\Annotation\Processor\ConsumesProcessor;
use Itspire\FrameworkExtraBundle\Util\Strategy\Annotation\Processor\ProducesProcessor;
use Itspire\FrameworkExtraBundle\Util\Strategy\Annotation\Processor\RouteProcessor;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class AnnotationHandler implements AnnotationHandlerInterface
{
    protected ?LoggerInterface $logger = null;

    /** @var AnnotationProcessorInterface[] */
    private array $processors = [];

    /** @var AnnotationProcessorInterface[] */
    private array $prioritizedProcessors = [];

    public function __construct(LoggerInterface $logger, iterable $annotationProcessors = [])
    {
        $this->logger = $logger;

        foreach ($annotationProcessors as $annotationProcessor) {
            $this->registerProcessor($annotationProcessor);
        }
    }

    public function registerProcessor(AnnotationProcessorInterface $annotationProcessor): self
    {
        $prioritizedProcessorsClass = [
            RouteProcessor::class,
            ProducesProcessor::class,
            ConsumesProcessor::class,
            BodyParamProcessor::class,
        ];

        $processorClass = get_class($annotationProcessor);
        $isPrioritized = false;

        // Mark prioritized if needed
        foreach ($prioritizedProcessorsClass as $prioritizedProcessorClass) {
            if (
                $annotationProcessor instanceof $prioritizedProcessorClass
                && false === array_key_exists($prioritizedProcessorClass, $this->prioritizedProcessors)
            ) {
                $this->prioritizedProcessors[$prioritizedProcessorClass] = $annotationProcessor;
                $isPrioritized = true;
            }
        }

        if (false === $isPrioritized && false === array_key_exists($processorClass, $this->processors)) {
            $this->processors[$processorClass] = $annotationProcessor;
        }

        return $this;
    }

    public function process(ControllerEvent $event, AnnotationInterface $annotation): void
    {
        // Handle prioritized first
        foreach ($this->prioritizedProcessors as $prioritizedProcessorClass => $processor) {
            if ($this->handleProcess($event, $processor, $annotation)) {
                return;
            }
        }

        // Then handle the others
        foreach ($this->processors as $processor) {
            if ($this->handleProcess($event, $processor, $annotation)) {
                return;
            }
        }

        $this->logger->error(
            sprintf(
                'No processor found for Annotation %s called in %s::process.',
                get_class($annotation),
                static::class
            )
        );

        throw new HttpException(
            new HttpExceptionDefinition(HttpExceptionDefinition::HTTP_INTERNAL_SERVER_ERROR)
        );
    }

    private function handleProcess(
        ControllerEvent $event,
        AnnotationProcessorInterface $annotationProcessor,
        AnnotationInterface $annotation
    ): bool {
        if (false === $annotationProcessor->supports($annotation)) {
            return false;
        }

        $annotationProcessor->process($event, $annotation);

        return true;
    }
}
