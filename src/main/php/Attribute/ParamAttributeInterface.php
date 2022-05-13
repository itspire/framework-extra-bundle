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
    public function getName(): ?string;

    public function getType(): ?string;

    public function getRequirements(): ?string;

    public function isRequired(): bool;
}
