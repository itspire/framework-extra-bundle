<?php

/*
 * Copyright (c) 2016 - 2024 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Attribute;

use Itspire\Common\Enum\MimeType;
use JMS\Serializer\Exclusion\GroupsExclusionStrategy;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Consumes implements AttributeInterface
{
    /** @var string[] */
    private array $consumableContentTypes = [];

    /** @var string[] */
    private array $deserializationGroups = [];

    /**
     * @param string|string[]|MimeType|MimeType[] $consumableContentTypes
     * @param string|string[] $deserializationGroups
     */
    public function __construct(
        mixed $consumableContentTypes = [],
        mixed $deserializationGroups = []
    ) {
        $this->setConsumableContentTypes($consumableContentTypes);
        $this->setDeserializationGroups($deserializationGroups);
    }

    public function getConsumableContentTypes(): array
    {
        return $this->consumableContentTypes;
    }

    public function getDeserializationGroups(): array
    {
        return $this->deserializationGroups;
    }

    /** @param string|string[]|MimeType|MimeType[] $consumableContentTypes */
    private function setConsumableContentTypes(mixed $consumableContentTypes): static
    {
        if (!is_array($consumableContentTypes)) {
            $consumableContentTypes = [$consumableContentTypes];
        }

        if (!empty($consumableContentTypes)) {
            $this->consumableContentTypes = array_map(
                static fn (mixed $consumableContentType): string => $consumableContentType instanceof MimeType
                    ? $consumableContentType->value
                    : $consumableContentType,
                $consumableContentTypes
            );
        }

        return $this;
    }

    /** @param string|string[] $deserializationGroups */
    private function setDeserializationGroups(mixed $deserializationGroups): static
    {
        if (!is_array($deserializationGroups)) {
            $deserializationGroups = [$deserializationGroups];
        }

        $this->deserializationGroups = $deserializationGroups ?: [GroupsExclusionStrategy::DEFAULT_GROUP];

        return $this;
    }
}
