<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Annotation;

interface ParamInterface extends AnnotationInterface
{
    public function setName(string $name): self;

    public function getName(): ?string;

    public function setType(string $type): self;

    public function getType(): ?string;

    public function setRequirements(string $requirements): self;

    public function getRequirements(): ?string;

    public function setRequired(bool $required): self;

    public function isRequired(): bool;
}
