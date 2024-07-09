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
        public ?string $name = null,
        public ?string $type = null,
        public bool $required = true,
        public ?string $requirements = null,
        public mixed $default = null
    ) {
    }

    /** @deprecated Use "name" property directly. */
    public function getName(): ?string
    {
        return $this->name;
    }

    /** @deprecated Use "type" property directly. */
    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /** @deprecated Use "type" property directly. */
    public function getType(): ?string
    {
        return $this->type;
    }

    /** @deprecated Use "required" property directly. */
    public function setRequired(bool $required): static
    {
        $this->required = $required;

        return $this;
    }

    /** @deprecated Use "required" property directly. */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /** @deprecated Use "requirements" property directly. */
    public function getRequirements(): ?string
    {
        return $this->requirements;
    }

    /** @deprecated Use "default" property directly. */
    public function setDefault(mixed $default = null): static
    {
        $this->default = $default;

        return $this;
    }

    /** @deprecated Use "default" property directly. */
    public function getDefault(): mixed
    {
        return $this->default;
    }
}
