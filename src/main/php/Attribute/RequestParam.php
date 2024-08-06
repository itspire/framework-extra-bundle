<?php

/*
 * Copyright (c) 2016 - 2024 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Attribute;

use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

/** @deprecated Use {@see MapRequestPayload} instead */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
class RequestParam extends AbstractParamAttribute
{
}
