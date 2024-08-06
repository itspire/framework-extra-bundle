<?php

/*
 * Copyright (c) 2016 - 2024 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Attribute;

use Itspire\Common\Enum\Http\HttpResponseStatus;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security as SensioSecurity;
use Symfony\Component\Security\Http\Attribute\IsGranted as SymfonyIsGranted;

/** @deprecated Use {@see SymfonyIsGranted} with Expression argument instead */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Security extends SensioSecurity implements AttributeInterface
{
    public function __construct(string $expression, private readonly ?HttpResponseStatus $responseStatus = null)
    {
        parent::__construct($expression, $responseStatus?->getDescription(), $responseStatus?->value);
    }

    public function getResponseStatus(): HttpResponseStatus
    {
        return $this->responseStatus;
    }
}
