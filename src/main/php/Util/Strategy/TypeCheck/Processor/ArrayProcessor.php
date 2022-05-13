<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck\Processor;

use Itspire\FrameworkExtraBundle\Attribute\ParamAttributeInterface;
use Symfony\Component\HttpFoundation\Request;

class ArrayProcessor extends AbstractTypeCheckProcessor
{
    public function process(ParamAttributeInterface $paramAttribute, Request $request, mixed $value): array
    {
        if (!is_array($value)) {
            $this->throwUnexpectedType($paramAttribute, $request, implode(', ', $this->getTypes()), $value);
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
