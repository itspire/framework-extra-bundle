<?php

/*
 * Copyright (c) 2016 - 2024 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\DependencyInjection;

use Itspire\Exception\Api\Adapter\ExceptionApiAdapterInterface;
use Itspire\Exception\Api\Mapper\ExceptionApiMapperInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class ItspireFrameworkExtraExtension extends Extension
{
    /** @throws \Exception */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container
            ->registerForAutoconfiguration(interface: ExceptionApiMapperInterface::class)
            ->addTag(name: 'itspire.framework_extra.exception_api_mapper');

        $container
            ->registerForAutoconfiguration(interface: ExceptionApiAdapterInterface::class)
            ->addTag(name: 'itspire.framework_extra.exception_api_adapter');

        $container->setParameter(
            name: Configuration::ALLOW_HTML_RESPONSE_PARAMETER,
            value: $config['allow_html_response_content_type']
        );

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load(resource: 'services.php');
    }
}
