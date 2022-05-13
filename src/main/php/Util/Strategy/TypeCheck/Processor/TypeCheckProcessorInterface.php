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

interface TypeCheckProcessorInterface
{
    public function process(ParamAttributeInterface $paramAttribute, Request $request, mixed $value): mixed;

    public function getTypes(): array;

    public function supports(string $type): bool;
}
