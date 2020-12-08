<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Unit\Fixtures;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class FixtureController extends AbstractController
{
    public function fixture(): void
    {
    }

    public function param($param): void
    {
    }

    public function typedParam(int $param): void
    {
    }
}
