<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $configurator) {

    $configurator->parameters()->set('locale', 'en');

    $services = $configurator->services()->defaults()->autowire()->autoconfigure();

    $services
        ->load('Itspire\\FrameworkExtraBundle\\Tests\\TestApp\\', '../src/')
        ->exclude('../Kernel.php');

    $services
        ->load('Itspire\\FrameworkExtraBundle\\Tests\\TestApp\\Controller\\', '../src/Controller/')
        ->tag('controller.service_arguments');
};
