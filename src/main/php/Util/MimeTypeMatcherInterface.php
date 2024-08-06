<?php

/*
 * Copyright (c) 2016 - 2024 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Util;

interface MimeTypeMatcherInterface
{
    /**
     * @param string[] $requestValues
     * @param string[] $attributeValues
     */
    public function findMimeTypeMatch(array $requestValues, array $attributeValues): ?string;
}
