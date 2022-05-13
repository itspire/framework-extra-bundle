<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Util\Strategy\Annotation;

use Itspire\FrameworkExtraBundle\Util\Strategy\Annotation\Processor\BodyParamProcessor;
use Itspire\FrameworkExtraBundle\Util\Strategy\Annotation\Processor\ConsumesProcessor;
use Itspire\FrameworkExtraBundle\Util\Strategy\Annotation\Processor\ProducesProcessor;
use Itspire\FrameworkExtraBundle\Util\Strategy\Annotation\Processor\RouteProcessor;
use Itspire\FrameworkExtraBundle\Util\Strategy\Attribute\AttributeHandler;

class AnnotationHandler extends AttributeHandler implements AnnotationHandlerInterface
{
    protected const PRIORITARY_PROCESSORS_CLASS = [
        RouteProcessor::class,
        ProducesProcessor::class,
        ConsumesProcessor::class,
        BodyParamProcessor::class,
    ];
}
