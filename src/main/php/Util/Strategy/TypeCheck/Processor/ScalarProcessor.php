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

class ScalarProcessor extends AbstractTypeCheckProcessor
{
    public function process(ParamAttributeInterface $paramAttribute, Request $request, mixed $value): mixed
    {
        if (false === is_scalar($value)) {
            $this->throwUnexpectedType($paramAttribute, $request, implode(', ', $this->getTypes()), $value);
        }

        $type = gettype($value);

        settype($value, $type);

        return $value;
    }

    public function getTypes(): array
    {
        return ['scalar'];
    }
}
