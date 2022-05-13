<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\EventListener;

use Doctrine\Common\Annotations\Reader;
use Itspire\FrameworkExtraBundle\Annotation\AnnotationInterface;
use Itspire\FrameworkExtraBundle\Attribute\AttributeInterface;
use Itspire\FrameworkExtraBundle\Util\Strategy\Annotation\AnnotationHandlerInterface;
use Itspire\FrameworkExtraBundle\Util\Strategy\Attribute\AttributeHandlerInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class ControllerListener
{
    public function __construct(
        private Reader $annotationsReader,
        private AnnotationHandlerInterface $annotationHandler,
        private AttributeHandlerInterface $attributeHandler
    ) {
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();

        if (is_array($controller)) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $reflectionClass = new \ReflectionClass(get_class($controller[0]));
            /** @noinspection PhpUnhandledExceptionInspection */
            $reflectionMethod = $reflectionClass->getMethod($controller[1]);

            foreach ($reflectionMethod->getAttributes() as $reflectionAttribute) {
                $attribute = $reflectionAttribute->newInstance();

                if ($attribute instanceof AttributeInterface) {
                    $this->attributeHandler->process($event, $attribute);
                }
            }

            $annotations = $this->annotationsReader->getMethodAnnotations($reflectionMethod);

            foreach ($annotations as $annotation) {
                if ($annotation instanceof AnnotationInterface) {
                    $this->annotationHandler->process($event, $annotation);
                }
            }
        }
    }
}
