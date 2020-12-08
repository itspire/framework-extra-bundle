<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck\Processor;

use Itspire\FrameworkExtraBundle\Annotation\ParamInterface;
use Symfony\Component\HttpFoundation\Request;

class BooleanProcessor extends AbstractTypeCheckProcessor
{
    public function process(ParamInterface $annotation, Request $request, $value): bool
    {
        $truthyValues = ['true', true, '1', 1];
        $falsyValues = ['false', false, '0', 0];

        // Strict comparison required otherwise all non-empty strings evaluate to boolean true
        if (!in_array($value, array_merge($truthyValues, $falsyValues), true)) {
            $this->throwUnexpectedType($annotation, $request, implode(', ', $this->getTypes()), $value);
        }

        return in_array($value, ['true', true, '1', 1], true);
    }

    public function getTypes(): array
    {
        return ['bool', 'boolean'];
    }
}
