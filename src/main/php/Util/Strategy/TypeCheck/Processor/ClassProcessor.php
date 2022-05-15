<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck\Processor;

use Itspire\FrameworkExtraBundle\Attribute\BodyParam;
use Itspire\FrameworkExtraBundle\Attribute\ParamAttributeInterface;
use Symfony\Component\HttpFoundation\Request;

class ClassProcessor extends AbstractTypeCheckProcessor
{
    public function process(ParamAttributeInterface $paramAttribute, Request $request, mixed $value): string
    {
        if (
            false === is_string($value)
            || false === $paramAttribute instanceof BodyParam
            || false === class_exists($paramAttribute->getClass())
        ) {
            $this->throwUnexpectedType($paramAttribute, $request, $paramAttribute->getClass(), $value);
        }

        return (string) $value;
    }

    public function getTypes(): array
    {
        return ['class'];
    }
}
