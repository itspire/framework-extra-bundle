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
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

abstract class AbstractAttributeProcessor
{
    public function __construct(protected LoggerInterface $logger)
    {
    }

    public function process(ControllerEvent $event, AttributeInterface $attribute): void
    {
        if (false === $this->supports($attribute)) {
            $this->logger->error(
                vsprintf(
                    format: 'Unsupported class "%s" used in "%s::process".',
                    values: [$attribute::class, static::class]
                )
            );

            throw new HttpException(HttpExceptionDefinition::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->handleProcess($event, $attribute);
    }

    abstract protected function handleProcess(ControllerEvent $event, AttributeInterface $attribute): void;

    protected function checkAlreadyProcessed(
        AttributeInterface $attribute,
        Request $request,
        callable $controller,
        string $attributeKey
    ): void {
        $reflectionClass = new \ReflectionClass(get_class($controller[0]));

        if (true === $request->attributes->has($attributeKey)) {
            $this->logger->error(
                vsprintf(
                    format: 'Duplicate usage of "%s" found on "%s::%s".',
                    values: [$attribute::class, $reflectionClass->getName(), $controller[1]]
                )
            );

            throw new HttpException(HttpExceptionDefinition::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
