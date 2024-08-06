<?php

/*
 * Copyright (c) 2016 - 2024 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Attribute;

use JetBrains\PhpStorm\Deprecated;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
class HeaderParam extends AbstractParamAttribute
{
    /**
     * @param string $name If attribute is on method OR on param and header real name is not identical to param name
     * @param string $headerName If attribute is on method and header real name is not identical to param name
     */
    public function __construct(
        ?string $name = null,
        public ?string $headerName = null,
        ?string $type = null,
        bool $required = true,
        ?string $requirements = null,
        mixed $default = null
    ) {
        parent::__construct($name, $type, $required, $requirements, $default);

        if (null === $this->headerName) {
            $this->headerName = $this->name;
        }
    }

    /** @deprecated Use "headerName" property directly. */
    public function getHeaderName(): ?string
    {
        return $this->headerName;
    }
}
