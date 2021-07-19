<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Annotation;

use Itspire\Common\Enum\Http\HttpResponseStatus;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security as BaseSecurity;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class Security extends BaseSecurity implements AnnotationInterface
{
    private ?HttpResponseStatus $responseStatus = null;

    public function getResponseStatus(): HttpResponseStatus
    {
        return $this->responseStatus;
    }

    public function setResponseStatus(int $responseStatusCode): self
    {
        if (!in_array($responseStatusCode, HttpResponseStatus::getRawValues(), true)) {
            throw new \InvalidArgumentException(
                'responseStatus should be a value from one of the constants in ' . HttpResponseStatus::class
            );
        }

        $this->responseStatus = new HttpResponseStatus($responseStatusCode);
        $this->statusCode = $this->responseStatus->getValue();
        $this->message = $this->responseStatus->getDescription();

        return $this;
    }
}
