<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\EventListener;

use Doctrine\Common\Annotations\Reader;
use Itspire\FrameworkExtraBundle\Annotation\AnnotationInterface;
use Itspire\FrameworkExtraBundle\Util\Strategy\Annotation\AnnotationHandlerInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class ControllerListener
{
    private ?Reader $annotationsReader = null;
    private ?AnnotationHandlerInterface $annotationHandler = null;

    public function __construct(Reader $annotationsReader, AnnotationHandlerInterface $annotationHandler)
    {
        $this->annotationsReader = $annotationsReader;
        $this->annotationHandler = $annotationHandler;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();

        if (is_array($controller)) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $reflectionClass = new \ReflectionClass(get_class($controller[0]));
            /** @noinspection PhpUnhandledExceptionInspection */
            $reflectionMethod = $reflectionClass->getMethod($controller[1]);
            $annotations = $this->annotationsReader->getMethodAnnotations($reflectionMethod);

            foreach ($annotations as $annotation) {
                if ($annotation instanceof AnnotationInterface) {
                    $this->annotationHandler->process($event, $annotation);
                }
            }
        }
    }
}
