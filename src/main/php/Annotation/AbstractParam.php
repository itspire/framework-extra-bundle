<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Annotation;

class AbstractParam extends AbstractAnnotation implements ParamInterface
{
    protected ?string $name = null;
    protected ?string $type = null;
    protected ?string $requirements = null;
    protected bool $required = true;

    public function __construct(array $configuration)
    {
        $this->defaultProperty = 'name';

        parent::__construct($configuration);
    }

    public function setName(string $name): ParamInterface
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setType(string $type): ParamInterface
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setRequirements(string $requirements): ParamInterface
    {
        $this->requirements = $requirements;

        return $this;
    }

    public function getRequirements(): ?string
    {
        return $this->requirements;
    }

    public function setRequired(bool $required): ParamInterface
    {
        $this->required = $required;

        return $this;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }
}
