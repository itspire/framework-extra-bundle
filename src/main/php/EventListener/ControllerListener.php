<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\EventListener;

use Itspire\FrameworkExtraBundle\Attribute\AbstractParamAttribute;
use Itspire\FrameworkExtraBundle\Attribute\AttributeInterface;
use Itspire\FrameworkExtraBundle\Attribute\BodyParam;
use Itspire\FrameworkExtraBundle\Attribute\ParamAttributeInterface;
use Itspire\FrameworkExtraBundle\Util\Strategy\Attribute\AttributeHandlerInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class ControllerListener
{
    public function __construct(private readonly AttributeHandlerInterface $attributeHandler)
    {
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();

        if (is_array($controller)) {
            $reflectionClass = new \ReflectionClass(get_class($controller[0]));
            $reflectionMethod = $reflectionClass->getMethod($controller[1]);

            foreach ($reflectionMethod->getParameters() as $reflectionParameter) {
                $this->processAttributes($event, $reflectionParameter->getAttributes(), $reflectionParameter);
            }
            $this->processAttributes($event, $reflectionMethod->getAttributes());
            $this->processAttributes($event, $reflectionClass->getAttributes());
        }
    }

    /** @param \ReflectionAttribute[] $attributes */
    private function processAttributes(
        ControllerEvent $event,
        array $attributes,
        ?\ReflectionParameter $reflectionParameter = null
    ): void {
        foreach ($attributes as $reflectionAttribute) {
            $attribute = $reflectionAttribute->newInstance();

            if ($attribute instanceof AttributeInterface) {
                $this->attributeHandler->process($event, $attribute, $reflectionParameter);
            }
        }
    }
}
