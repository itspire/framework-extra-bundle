<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Annotation;

use Itspire\Http\Common\Enum\HttpResponseStatus;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security as BaseSecurity;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class Security extends BaseSecurity implements AnnotationInterface
{
    private ?array $responseStatus = null;

    public function getResponseStatus(): array
    {
        return $this->responseStatus;
    }

    public function setResponseStatus(array $responseStatus): self
    {
        if (!in_array($responseStatus, HttpResponseStatus::getRawValues(), true)) {
            throw new \InvalidArgumentException(
                'responseStatus should be a value from one of the constants in ' . HttpResponseStatus::class
            );
        }

        $this->responseStatus = $responseStatus;
        $this->statusCode = $responseStatus[0];
        $this->message = $responseStatus[1];

        return $this;
    }
}
