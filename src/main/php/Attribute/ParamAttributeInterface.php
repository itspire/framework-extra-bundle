<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Attribute;

interface ParamAttributeInterface extends AttributeInterface
{
    /** @deprecated Use "name" property directly. */
    public function getName(): ?string;

    /** @deprecated Use "type" property directly. */
    public function getType(): ?string;

    /** @deprecated Use "requirements" property directly. */
    public function getRequirements(): ?string;

    /** @deprecated Use "required" property directly. */
    public function isRequired(): bool;
}
