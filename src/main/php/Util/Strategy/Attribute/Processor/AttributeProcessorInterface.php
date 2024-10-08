<?php

/*
 * Copyright (c) 2016 - 2024 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Util\Strategy\Attribute\Processor;

use Itspire\FrameworkExtraBundle\Attribute\AttributeInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

interface AttributeProcessorInterface
{
    public function process(
        ControllerEvent $event,
        AttributeInterface $attribute,
        ?\ReflectionParameter $reflectionParameter = null
    ): void;

    public function supports(AttributeInterface $attribute): bool;
}
