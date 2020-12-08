<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Util\Strategy\Annotation;

use Itspire\FrameworkExtraBundle\Annotation\AnnotationInterface;
use Itspire\FrameworkExtraBundle\Util\Strategy\Annotation\Processor\AnnotationProcessorInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

interface AnnotationHandlerInterface
{
    public function registerProcessor(AnnotationProcessorInterface $annotationProcessor): self;

    public function process(ControllerEvent $event, AnnotationInterface $annotation): void;
}
