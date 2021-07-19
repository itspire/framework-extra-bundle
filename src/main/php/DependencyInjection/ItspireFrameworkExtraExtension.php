<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\DependencyInjection;

use Itspire\Exception\Api\Adapter\ExceptionAdapterInterface;
use Itspire\Exception\Api\Mapper\ExceptionMapperInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class ItspireFrameworkExtraExtension extends Extension
{
    /** @throws \Exception */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container
            ->registerForAutoconfiguration(ExceptionMapperInterface::class)
            ->addTag('itspire.framework_extra.exception_mapper');

        $container
            ->registerForAutoconfiguration(ExceptionAdapterInterface::class)
            ->addTag('itspire.framework_extra.exception_adapter');

        $container->setParameter(
            Configuration::ALLOW_HTML_RESPONSE_PARAMETER,
            $config['allow_html_response_content_type']
        );

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.php');
    }
}
