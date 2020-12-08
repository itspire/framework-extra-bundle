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
class HeaderParam extends AbstractParam
{
    private ?string $headerName = null;

    public function __construct(array $configuration)
    {
        parent::__construct($configuration);

        if (null === $this->headerName) {
            $this->headerName = $this->name;
        }
    }

    public function setHeaderName(string $headerName): self
    {
        $this->headerName = $headerName;

        return $this;
    }

    public function getHeaderName(): ?string
    {
        return $this->headerName;
    }
}
