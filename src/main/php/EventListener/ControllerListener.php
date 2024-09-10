<?php

/*
 * Copyright (c) 2016 - 2024 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\EventListener;

use Itspire\FrameworkExtraBundle\Attribute\AbstractParamAttribute;
use Itspire\FrameworkExtraBundle\Attribute\AttributeInterface;
use Itspire\FrameworkExtraBundle\Attribute\BodyParam;
use Itspire\FrameworkExtraBundle\Attribute\ParamAttributeInterface;
use Itspire\FrameworkExtraBundle\Attribute\Route;
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

            $reflectionClassAttributes = $reflectionClass->getAttributes();
            $reflectionMethodAttributes = $reflectionMethod->getAttributes();

            // Ensures each Route attribute is handled first and only once for the request
            foreach ($reflectionClassAttributes as $key => $reflectionClassAttribute) {
                if ($reflectionClassAttribute instanceof Route) {
                    $this->processAttributes($event, [$reflectionClassAttribute]);

                    unset($reflectionClassAttributes[$key]);
                }
            }

            foreach ($reflectionMethodAttributes as $key => $reflectionMethodAttribute) {
                if ($reflectionMethodAttribute instanceof Route) {
                    $this->processAttributes($event, [$reflectionMethodAttribute]);

                    unset($reflectionMethodAttributes[$key]);
                }
            }

            $this->processAttributes($event, $reflectionClassAttributes);
            $this->processAttributes($event, $reflectionMethodAttributes);

            foreach ($reflectionMethod->getParameters() as $reflectionParameter) {
                $this->processAttributes($event, $reflectionParameter->getAttributes(), $reflectionParameter);
            }
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
