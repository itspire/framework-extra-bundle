<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\TestApp;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function getProjectDir(): string
    {
        return realpath(__DIR__ . '/../../../../../');
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/itspire/framework-extra-bundle/var/' . $this->environment . '/cache';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/itspire/framework-extra-bundle/var/' . $this->environment . '/log';
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function getConfigDir(): string
    {
        return $this->getProjectDir() . '/src/test/php/TestApp/config';
    }
}
