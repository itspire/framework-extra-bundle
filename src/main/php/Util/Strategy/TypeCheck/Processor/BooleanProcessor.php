<?php

/*
 * Copyright (c) 2016 - 2024 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck\Processor;

use Itspire\FrameworkExtraBundle\Attribute\ParamAttributeInterface;
use Symfony\Component\HttpFoundation\Request;

class BooleanProcessor extends AbstractTypeCheckProcessor
{
    public function process(ParamAttributeInterface $paramAttribute, Request $request, mixed $value): bool
    {
        $truthyValues = ['true', true, '1', 1];
        $falsyValues = ['false', false, '0', 0];

        // Strict comparison required otherwise all non-empty strings evaluate to boolean true
        if (!in_array(needle: $value, haystack: array_merge($truthyValues, $falsyValues), strict: true)) {
            $this->throwUnexpectedType(
                $paramAttribute,
                $request,
                implode(separator: ', ', array: $this->getTypes()),
                $value
            );
        }

        return in_array(needle: $value, haystack: ['true', true, '1', 1], strict: true);
    }

    public function getTypes(): array
    {
        return ['bool', 'boolean'];
    }
}
