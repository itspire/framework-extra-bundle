<?php

/*
 * Copyright (c) 2016 - 2024 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Unit\Fixtures;

use Itspire\Common\Enum\Http\HttpResponseStatus;
use Itspire\FrameworkExtraBundle\Attribute as ItspireFrameworkExtra;
use Itspire\FrameworkExtraBundle\Tests\TestApp\Model\TestObject;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class FixtureController extends AbstractController
{
    public function fixture(): void
    {
    }

    #[ItspireFrameworkExtra\Route(path: '/test', responseStatus: HttpResponseStatus::HTTP_OK)]
    public function fixtureWithAttribute(): void
    {
    }

    #[ItspireFrameworkExtra\BodyParam(name: 'param', type: 'class', class: TestObject::class)]
    public function bodyParam($param): void
    {
    }

    public function param($param): void
    {
    }

    public function typedParam(int $param): void
    {
    }
}
