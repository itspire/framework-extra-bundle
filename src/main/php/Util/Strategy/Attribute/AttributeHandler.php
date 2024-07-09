<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Util\Strategy\Attribute;

use Itspire\Exception\Definition\Http\HttpExceptionDefinition;
use Itspire\Exception\Http\HttpException;
use Itspire\FrameworkExtraBundle\Attribute\AttributeInterface;
use Itspire\FrameworkExtraBundle\Util\Strategy\Attribute\Processor\BodyParamProcessor;
use Itspire\FrameworkExtraBundle\Util\Strategy\Attribute\Processor\ConsumesProcessor;
use Itspire\FrameworkExtraBundle\Util\Strategy\Attribute\Processor\ProducesProcessor;
use Itspire\FrameworkExtraBundle\Util\Strategy\Attribute\Processor\RouteProcessor;
use Itspire\FrameworkExtraBundle\Util\Strategy\Attribute\Processor\AttributeProcessorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class AttributeHandler implements AttributeHandlerInterface
{
    protected const PRIORITARY_PROCESSORS_CLASS = [
        RouteProcessor::class,
        ProducesProcessor::class,
        ConsumesProcessor::class,
        BodyParamProcessor::class,
    ];

    /** @var AttributeProcessorInterface[] */
    protected array $processors = [];

    /** @var AttributeProcessorInterface[] */
    protected array $prioritizedProcessors = [];

    public function __construct(protected readonly LoggerInterface $logger, iterable $processors = [])
    {
        foreach ($processors as $processor) {
            $this->registerProcessor($processor);
        }
    }

    public function registerProcessor(AttributeProcessorInterface $attributeProcessor): self
    {
        $isPrioritized = false;

        // Mark prioritized if needed
        foreach (static::PRIORITARY_PROCESSORS_CLASS as $prioritizedProcessorClass) {
            if (
                $attributeProcessor instanceof $prioritizedProcessorClass
                && false === array_key_exists($prioritizedProcessorClass, $this->prioritizedProcessors)
            ) {
                $this->prioritizedProcessors[$prioritizedProcessorClass] = $attributeProcessor;
                $isPrioritized = true;
            }
        }

        if (
            false === $isPrioritized
            && false === array_key_exists($attributeProcessor::class, $this->processors)
        ) {
            $this->processors[$attributeProcessor::class] = $attributeProcessor;
        }

        return $this;
    }

    public function process(
        ControllerEvent $event,
        AttributeInterface $attribute,
        ?\ReflectionParameter $reflectionParameter = null
    ): void {
        // Handle prioritized first
        foreach ($this->prioritizedProcessors as $processor) {
            if ($this->handleProcess($event, $processor, $attribute, $reflectionParameter)) {
                return;
            }
        }

        // Then handle the others
        foreach ($this->processors as $processor) {
            if ($this->handleProcess($event, $processor, $attribute, $reflectionParameter)) {
                return;
            }
        }

        $this->logger->error(
            vsprintf(
                format: 'No processor found for attribute of class %s called in %s::process.',
                values: [$attribute::class, static::class]
            )
        );

        throw new HttpException(HttpExceptionDefinition::HTTP_INTERNAL_SERVER_ERROR);
    }

    private function handleProcess(
        ControllerEvent $event,
        AttributeProcessorInterface $processor,
        AttributeInterface $attribute,
        ?\ReflectionParameter $reflectionParameter = null
    ): bool {
        if (false === $processor->supports($attribute)) {
            return false;
        }

        $processor->process($event, $attribute, $reflectionParameter);

        return true;
    }
}
