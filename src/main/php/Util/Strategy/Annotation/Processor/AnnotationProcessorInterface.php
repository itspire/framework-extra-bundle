<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Util\Strategy\Annotation\Processor;

use Itspire\FrameworkExtraBundle\Annotation\AnnotationInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

interface AnnotationProcessorInterface
{
    public function process(ControllerEvent $event, AnnotationInterface $annotation): void;

    public function supports(AnnotationInterface $annotation): bool;
}
