<?php

/*
 * Copyright (c) 2016 - 2024 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
class FileParam extends AbstractParamAttribute
{
    public function __construct(?string $name = null, bool $required = true, ?string $requirements = null)
    {
        parent::__construct($name, 'file', $required, $requirements);
    }
}
