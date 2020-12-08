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
class FileParam extends AbstractParam
{
    public function __construct(array $configuration)
    {
        // Enforcing the correct values
        $configuration['type'] = 'file';

        parent::__construct($configuration);
    }
}
