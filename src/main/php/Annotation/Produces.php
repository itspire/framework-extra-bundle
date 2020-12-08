<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Annotation;

/**
 * @Annotation
 * @Target("METHOD")
 */
class Produces extends AbstractAnnotation
{
    private array $acceptableFormats = [];
    private array $serializationGroups = ['Default'];

    public function __construct(array $configuration)
    {
        $this->defaultProperty = 'acceptable_formats';

        parent::__construct($configuration);
    }

    /** @param string|string[] $acceptableFormats */
    public function setAcceptableFormats($acceptableFormats): self
    {
        if (!is_array($acceptableFormats)) {
            $acceptableFormats = [$acceptableFormats];
        }

        $this->acceptableFormats = $acceptableFormats;

        return $this;
    }

    public function getAcceptableFormats(): array
    {
        return $this->acceptableFormats;
    }

    /** @param string|string[] $serializationGroups */
    public function setSerializationGroups($serializationGroups): self
    {
        if (!is_array($serializationGroups)) {
            $serializationGroups = [$serializationGroups];
        }

        $this->serializationGroups = $serializationGroups;

        return $this;
    }

    public function getSerializationGroups(): array
    {
        return $this->serializationGroups;
    }
}
