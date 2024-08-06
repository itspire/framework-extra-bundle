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

class IntegerProcessor extends AbstractTypeCheckProcessor
{
    public function process(ParamAttributeInterface $paramAttribute, Request $request, mixed $value): int
    {
        if (false === is_numeric($value) || (int) $value != $value) {
            $this->throwUnexpectedType($paramAttribute, $request, implode(', ', $this->getTypes()), $value);
        }

        return (int) $value;
    }

    public function getTypes(): array
    {
        return ['int', 'integer'];
    }
}
