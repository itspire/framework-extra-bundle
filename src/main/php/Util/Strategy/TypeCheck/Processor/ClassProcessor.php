<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck\Processor;

use Itspire\FrameworkExtraBundle\Annotation\BodyParam;
use Itspire\FrameworkExtraBundle\Annotation\ParamInterface;
use Symfony\Component\HttpFoundation\Request;

class ClassProcessor extends AbstractTypeCheckProcessor
{
    public function process(ParamInterface $annotation, Request $request, $value): string
    {
        if (
            false === is_string($value)
            || false === $annotation instanceof BodyParam
            || false === class_exists($annotation->getClass())
        ) {
            $this->throwUnexpectedType($annotation, $request, 'string', $value);
        }

        return (string) $value;
    }

    public function getTypes(): array
    {
        return ['class'];
    }
}
