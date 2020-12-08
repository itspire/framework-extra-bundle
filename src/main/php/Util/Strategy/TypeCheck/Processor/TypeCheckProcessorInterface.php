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

interface TypeCheckProcessorInterface
{
    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public function process(ParamInterface $annotation, Request $request, $value);

    public function getTypes(): array;

    public function supports(string $type): bool;
}
