<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Attribute;

class AbstractParamAttribute implements ParamAttributeInterface
{
    public function __construct(
        protected ?string $name = null,
        protected ?string $type = null,
        protected bool $required = true,
        protected ?string $requirements = null,
        protected mixed $default = null
    ) {
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setRequired(bool $required): static
    {
        $this->required = $required;

        return $this;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function getRequirements(): ?string
    {
        return $this->requirements;
    }

    public function setDefault(mixed $default = null): static
    {
        $this->default = $default;

        return $this;
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }
}
