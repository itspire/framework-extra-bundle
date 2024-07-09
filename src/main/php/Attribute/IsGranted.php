<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Attribute;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted as SensioIsGranted;
use Symfony\Component\Security\Http\Attribute\IsGranted as SymfonyIsGranted;

/** @deprecated Use {@see SymfonyIsGranted} instead */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class IsGranted extends SensioIsGranted implements AttributeInterface
{
    public function __construct(
        array | string | \BackedEnum $data = [],
        $subject = null,
        string $message = null,
        ?int $statusCode = null
    ) {
        parent::__construct(
            $data instanceof \BackedEnum ? $data->value : $data,
            $subject,
            $message,
            $statusCode
        );
    }
}
