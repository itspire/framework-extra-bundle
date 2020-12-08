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
class QueryParam extends AbstractParam
{
    public function __construct(array $configuration)
    {
        // query params are not required by default
        parent::__construct(array_merge(['required' => false], $configuration));
    }
}
