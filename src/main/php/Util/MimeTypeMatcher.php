<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Util;

class MimeTypeMatcher implements MimeTypeMatcherInterface
{
    /**
     * @param string[] $requestValues
     * @param string[] $annotationValues
     */
    public function findMimeTypeMatch(array $requestValues, array $annotationValues): ?string
    {
        $splitAnnotations = [];
        foreach ($annotationValues as $key => $annotationValue) {
            if (!empty($annotationValue)) {
                $splitAnnotations[$key] = explode('/', $annotationValue);
            }
        }

        foreach ($requestValues as $requestValue) {
            if (!empty($requestValue)) {
                if ('*/*' === $requestValue) {
                    return $annotationValues[0];
                }

                if (in_array($requestValue, $annotationValues)) {
                    return $requestValue;
                }

                $requestValueParts = explode('/', $requestValue);
                foreach ($splitAnnotations as $key => $splitAnnotation) {
                    if (
                        ($requestValueParts[0] === $splitAnnotation[0] && '*' === $requestValueParts[1])
                        || ($requestValueParts[1] === $splitAnnotation[1] && '*' === $requestValueParts[0])
                    ) {
                        return $annotationValues[$key];
                    }
                }
            }
        }

        return null;
    }
}
