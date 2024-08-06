<?php

/*
 * Copyright (c) 2016 - 2024 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Util\Strategy\Attribute;

use Itspire\FrameworkExtraBundle\Attribute\AttributeInterface;
use Itspire\FrameworkExtraBundle\Util\Strategy\Attribute\Processor\AttributeProcessorInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

interface AttributeHandlerInterface
{
    public function registerProcessor(AttributeProcessorInterface $attributeProcessor): self;

    public function process(
        ControllerEvent $event,
        AttributeInterface $attribute,
        ?\ReflectionParameter $reflectionParameter = null
    ): void;
}
