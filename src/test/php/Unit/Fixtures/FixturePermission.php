<?php

/*
 * Copyright (c) 2016 - 2024 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Unit\Fixtures;

use Itspire\Common\Enum\ExtendedBackedEnumInterface;
use Itspire\Common\Enum\ExtendedBackedEnumTrait;

enum FixturePermission: string implements ExtendedBackedEnumInterface
{
    use ExtendedBackedEnumTrait;

    case TEST = 'TEST';
}
