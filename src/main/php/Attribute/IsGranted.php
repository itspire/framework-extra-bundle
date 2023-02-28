<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Attribute;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted as BaseIsGranted;

#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class IsGranted extends BaseIsGranted implements AttributeInterface
{
    public function __construct(
        array | string | \UnitEnum $data = [],
        $subject = null,
        string $message = null,
        ?int $statusCode = null
    ) {
        parent::__construct(
            $data instanceof \UnitEnum ? $data->name : $data,
            $subject,
            $message,
            $statusCode
        );
    }
}
