<?php

/*
 * Copyright (c) 2016 - 2024 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $configurator) {

    $configurator->parameters()->set(name: 'locale', value: 'en');

    $services = $configurator->services()->defaults()->autowire()->autoconfigure();

    $services
        ->load(namespace: 'Itspire\\FrameworkExtraBundle\\Tests\\TestApp\\', resource: '../src/')
        ->exclude(excludes: '../Kernel.php');

    $services
        ->load(namespace: 'Itspire\\FrameworkExtraBundle\\Tests\\TestApp\\Controller\\', resource: '../src/Controller/')
        ->tag(name: 'controller.service_arguments');
};
