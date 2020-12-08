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

class ArrayProcessor extends AbstractTypeCheckProcessor
{
    public function process(ParamInterface $annotation, Request $request, $value): array
    {
        // Strict comparison required otherwise all non-empty strings evaluate to boolean true
        if (!is_array($value)) {
            $this->throwUnexpectedType($annotation, $request, implode(', ', $this->getTypes()), $value);
        }

        array_walk_recursive(
            $value,
            function (&$data) {
                // Retrieve the initial type first then trim value and reset as original type
                $type = gettype($data);
                $data = trim((string) $data);
                settype($data, $type);
            }
        );

        return $value;
    }

    public function getTypes(): array
    {
        return ['array'];
    }
}
