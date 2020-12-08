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
class Consumes extends AbstractAnnotation
{
    private array $consumableContentTypes = [];
    private array $deserializationGroups = [];

    public function __construct(array $configuration)
    {
        $this->defaultProperty = 'consumable_content_types';

        parent::__construct($configuration);
    }

    /** @param string|string[] $consumableContentTypes */
    public function setConsumableContentTypes($consumableContentTypes): self
    {
        if (!is_array($consumableContentTypes)) {
            $consumableContentTypes = [$consumableContentTypes];
        }

        $this->consumableContentTypes = $consumableContentTypes;

        return $this;
    }

    public function getConsumableContentTypes(): array
    {
        return $this->consumableContentTypes;
    }

    /** @param string|string[] $deserializationGroups */
    public function setDeserializationGroups($deserializationGroups): self
    {
        if (!is_array($deserializationGroups)) {
            $deserializationGroups = [$deserializationGroups];
        }

        $this->deserializationGroups = $deserializationGroups;

        return $this;
    }

    public function getDeserializationGroups(): array
    {
        return $this->deserializationGroups;
    }
}
