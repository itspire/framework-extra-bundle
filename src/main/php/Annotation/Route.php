<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Annotation;

use Itspire\Http\Common\Enum\HttpResponseStatus;
use Symfony\Component\Routing\Annotation\Route as BaseRoute;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class Route extends BaseRoute implements AnnotationInterface
{
    /** @var int|array */
    private $responseStatus;

    /** @return int|array */
    public function getResponseStatus()
    {
        return $this->responseStatus;
    }

    public function setResponseStatus($responseStatus): self
    {
        if (
            (!is_int($responseStatus) && !is_array($responseStatus))
            || (is_array($responseStatus) && !in_array($responseStatus, HttpResponseStatus::getRawValues(), true))
        ) {
            throw new \InvalidArgumentException(
                'responseStatus should be an int or one of the constants from ' . HttpResponseStatus::class
            );
        }

        $this->responseStatus = $responseStatus;

        return $this;
    }

    public function getResponseStatusCode(): ?int
    {
        return (is_array($this->responseStatus)) ? $this->responseStatus[0] : $this->responseStatus;
    }
}
