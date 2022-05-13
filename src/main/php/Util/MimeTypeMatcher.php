<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Util;

class MimeTypeMatcher implements MimeTypeMatcherInterface
{
    /**
     * @param string[] $requestValues
     * @param string[] $attributeValues
     */
    public function findMimeTypeMatch(array $requestValues, array $attributeValues): ?string
    {
        $attributesParts = [];
        foreach ($attributeValues as $key => $attributeValue) {
            if (!empty($attributeValue)) {
                $attributesParts[$key] = explode('/', $attributeValue);
            }
        }

        foreach ($requestValues as $requestValue) {
            if (!empty($requestValue)) {
                if ('*/*' === $requestValue) {
                    return $attributeValues[0];
                }

                if (in_array($requestValue, $attributeValues)) {
                    return $requestValue;
                }

                $requestValueParts = explode('/', $requestValue);
                foreach ($attributesParts as $key => $attributeParts) {
                    if (
                        ($requestValueParts[0] === $attributeParts[0] && '*' === $requestValueParts[1])
                        || ($requestValueParts[1] === $attributeParts[1] && '*' === $requestValueParts[0])
                    ) {
                        return $attributeValues[$key];
                    }
                }
            }
        }

        return null;
    }
}
