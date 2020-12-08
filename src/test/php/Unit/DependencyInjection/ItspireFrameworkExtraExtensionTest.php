<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Unit\DependencyInjection;

use Itspire\FrameworkExtraBundle\DependencyInjection\Configuration;
use Itspire\FrameworkExtraBundle\DependencyInjection\ItspireFrameworkExtraExtension;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ItspireFrameworkExtraExtensionTest extends TestCase
{
    /**
     * @test
     * @throws \Exception
     */
    public function loadWithAllowedHtmlResponseContentTypeTest(): void
    {
        $container = $this->getContainerBuilder();
        $extension = new ItspireFrameworkExtraExtension();
        $extension->load($this->getConfigs(true), $container);

        static::assertTrue($container->hasParameter(Configuration::ALLOW_HTML_RESPONSE_PARAMETER));
        static::assertTrue($container->getParameter(Configuration::ALLOW_HTML_RESPONSE_PARAMETER));
    }

    /**
     * @test
     * @throws \Exception
     */
    public function loadWithDisallowedHtmlResponseContentTypeTest(): void
    {
        $container = $this->getContainerBuilder();
        $extension = new ItspireFrameworkExtraExtension();
        $extension->load($this->getConfigs(false), $container);

        static::assertTrue($container->hasParameter(Configuration::ALLOW_HTML_RESPONSE_PARAMETER));
        static::assertFalse($container->getParameter(Configuration::ALLOW_HTML_RESPONSE_PARAMETER));
    }

    /**
     * @test
     * @throws \Exception
     */
    public function loadWithDefaultAllowHtmlResponseContentTypeTest(): void
    {
        $container = $this->getContainerBuilder();
        $extension = new ItspireFrameworkExtraExtension();
        $extension->load($this->getConfigs(), $container);

        static::assertTrue($container->hasParameter(Configuration::ALLOW_HTML_RESPONSE_PARAMETER));
        static::assertFalse($container->getParameter(Configuration::ALLOW_HTML_RESPONSE_PARAMETER));
    }

    private function getContainerBuilder(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', true);
        $container->setParameter('kernel.project_dir', realpath(__DIR__ . '/../../../../../'));
        $container->set('event_dispatcher', $this->getMockBuilder(EventDispatcherInterface::class)->getMock());
        $container->set('logger', $this->getMockBuilder(LoggerInterface::class)->getMock());

        return $container;
    }

    private function getConfigs(?bool $allowedHtmlResponseContentType = null): array
    {
        $config = ['itspire_framework_extra' => []];

        if (null !== $allowedHtmlResponseContentType) {
            $config['itspire_framework_extra']['allow_html_response_content_type'] = $allowedHtmlResponseContentType;
        }

        return $config;
    }
}
