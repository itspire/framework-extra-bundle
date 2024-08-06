<?php

/*
 * Copyright (c) 2016 - 2024 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Util\Strategy\Attribute\Processor;

use Itspire\FrameworkExtraBundle\Attribute\AttributeInterface;
use Itspire\FrameworkExtraBundle\Attribute\Route;
use Itspire\FrameworkExtraBundle\Configuration\CustomRequestAttributes;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class RouteProcessor extends AbstractAttributeProcessor
{
    public function supports(AttributeInterface $attribute): bool
    {
        return $attribute instanceof Route;
    }

    /** @param Route $attribute */
    protected function handleProcess(
        ControllerEvent $event,
        AttributeInterface $attribute,
        ?\ReflectionParameter $reflectionParameter = null
    ): void {
        $request = $event->getRequest();
        $request->attributes->set(key: CustomRequestAttributes::ROUTE_CALLED, value: true);

        if (null !== $attribute->getResponseStatus()) {
            $request->attributes->set(
                key: CustomRequestAttributes::RESPONSE_STATUS_CODE,
                value: $attribute->getResponseStatus()->value
            );
        }
    }
}
