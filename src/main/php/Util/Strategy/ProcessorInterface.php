<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Util\Strategy;

use Itspire\FrameworkExtraBundle\Attribute\AttributeInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * Parent interface for attribute and annotation processors
 * @deprecated Will be removed in 3.0 with the removal of annotations processors
 */
interface ProcessorInterface
{
    public function process(ControllerEvent $event, AttributeInterface $attribute): void;

    public function supports(AttributeInterface $attribute): bool;
}
