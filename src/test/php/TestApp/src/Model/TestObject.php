<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\TestApp\Model;

use JMS\Serializer\Annotation as Serializer;

#[Serializer\XmlRoot('testObject')]
class TestObject
{
    #[Serializer\XmlAttribute]
    #[Serializer\SerializedName('testProperty')]
    #[Serializer\Type('string')]
    private ?string $testProperty = null;

    #[Serializer\XmlAttribute]
    #[Serializer\SerializedName('testProperty2')]
    #[Serializer\Type('int')]
    #[Serializer\Groups(['extended'])]
    private ?int $testProperty2 = null;

    public function getTestProperty(): ?string
    {
        return $this->testProperty;
    }

    public function setTestProperty(string $testProperty): self
    {
        $this->testProperty = $testProperty;

        return $this;
    }

    public function getTestProperty2(): ?int
    {
        return $this->testProperty2;
    }

    public function setTestProperty2(int $testProperty2): self
    {
        $this->testProperty2 = $testProperty2;

        return $this;
    }
}
