<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Attribute;

#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_METHOD)]
class FileParam extends AbstractParamAttribute
{
    public function __construct(string $name, bool $required = true, ?string $requirements = null)
    {
        parent::__construct($name, 'file', $required, $requirements);
    }
}
