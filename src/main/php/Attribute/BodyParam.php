<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD)]
class BodyParam extends AbstractParamAttribute
{
    public function __construct(
        string $name,
        ?string $type = null,
        private ?string $class = null,
        bool $required = true,
        ?string $requirements = null
    ) {
        parent::__construct($name, $type, $required, $requirements);
    }

    public function getClass(): ?string
    {
        return $this->class;
    }
}
