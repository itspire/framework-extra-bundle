<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Annotation;

use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Itspire\Common\Enum\Http\HttpResponseStatus;
use Itspire\FrameworkExtraBundle\Attribute\Security as AttributeSecurity;

/**
 * @deprecated
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"CLASS", "METHOD"})
 */
class Security extends AttributeSecurity implements AnnotationInterface
{
    public function __construct(string $expression, HttpResponseStatus | int | null $responseStatus = null)
    {
        if (null !== $responseStatus && !$responseStatus instanceof HttpResponseStatus) {
            $responseStatus = HttpResponseStatus::tryFrom($responseStatus);
            if (null === $responseStatus) {
                throw new \InvalidArgumentException(
                    'responseStatus should be a value from one of the values in enum ' . HttpResponseStatus::class
                );
            }
        }

        parent::__construct($expression, $responseStatus);
    }
}
