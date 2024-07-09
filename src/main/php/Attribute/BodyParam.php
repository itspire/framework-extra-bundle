<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Attribute;

use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

/** @deprecated Use {@see MapRequestPayload} instead */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_PARAMETER)]
class BodyParam extends AbstractParamAttribute
{
    public function __construct(
        ?string $name = null,
        ?string $type = null,
        public ?string $class = null,
        bool $required = true,
        ?string $requirements = null
    ) {
        parent::__construct($name, $type, $required, $requirements);
    }

    /** @deprecated Use "class" property directly. */
    public function getClass(): ?string
    {
        return $this->class;
    }
}
