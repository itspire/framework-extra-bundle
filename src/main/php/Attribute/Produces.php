<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Attribute;

use Itspire\Common\Enum\MimeType;
use JMS\Serializer\Exclusion\GroupsExclusionStrategy;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Produces implements AttributeInterface
{
    /** @var string[] */
    private array $acceptableFormats = [];

    /** @var string[] */
    private array $serializationGroups = [GroupsExclusionStrategy::DEFAULT_GROUP];

    /**
     * @param string|string[]|MimeType|MimeType[] $acceptableFormats
     * @param string|string[] $serializationGroups
     */
    public function __construct(
        mixed $acceptableFormats = [],
        mixed $serializationGroups = []
    ) {
        $this->setAcceptableFormats($acceptableFormats);
        $this->setSerializationGroups($serializationGroups);
    }

    public function getAcceptableFormats(): array
    {
        return $this->acceptableFormats;
    }

    public function getSerializationGroups(): array
    {
        return $this->serializationGroups;
    }

    /** @param string|string[]|MimeType|MimeType[] $acceptableFormats */
    private function setAcceptableFormats(mixed $acceptableFormats): static
    {
        if (!is_array($acceptableFormats)) {
            $acceptableFormats = [$acceptableFormats];
        }

        if (!empty($acceptableFormats)) {
            $this->acceptableFormats = array_map(
                static fn (mixed $acceptableFormat): string => $acceptableFormat instanceof MimeType
                    ? $acceptableFormat->value
                    : $acceptableFormat,
                $acceptableFormats
            );
        }

        return $this;
    }

    /** @param string|string[] $serializationGroups */
    private function setSerializationGroups(mixed $serializationGroups): static
    {
        if (!is_array($serializationGroups)) {
            $serializationGroups = [$serializationGroups];
        }

        $this->serializationGroups = $serializationGroups ?: [GroupsExclusionStrategy::DEFAULT_GROUP];

        return $this;
    }
}
