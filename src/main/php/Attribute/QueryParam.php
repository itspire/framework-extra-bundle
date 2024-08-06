<?php

/*
 * Copyright (c) 2016 - 2024 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Attribute;

use Itspire\Common\Enum\MimeType;

/** @deprecated Use {@see MapQueryParameter} instead */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
class QueryParam extends AbstractParamAttribute
{
    public function __construct(
        ?string $name = null,
        ?string $type = null,
        bool $required = false,
        ?string $requirements = null,
        mixed $default = null
    ) {
        parent::__construct($name, $type, $required, $requirements, $default);
    }
}
