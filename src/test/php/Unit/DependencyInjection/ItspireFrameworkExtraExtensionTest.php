<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Unit\DependencyInjection;

use Itspire\FrameworkExtraBundle\DependencyInjection\Configuration;
use Itspire\FrameworkExtraBundle\DependencyInjection\ItspireFrameworkExtraExtension;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ItspireFrameworkExtraExtensionTest extends TestCase
{
    /** @throws \Exception */
    #[Test]
    public function loadWithAllowedHtmlResponseContentTypeTest(): void
    {
        $container = $this->getContainerBuilder();
        (new ItspireFrameworkExtraExtension())->load($this->getConfigs(true), $container);

        static::assertTrue(condition: $container->hasParameter(Configuration::ALLOW_HTML_RESPONSE_PARAMETER));
        static::assertTrue(condition: $container->getParameter(Configuration::ALLOW_HTML_RESPONSE_PARAMETER));
    }

    /** @throws \Exception */
    #[Test]
    public function loadWithDisallowedHtmlResponseContentTypeTest(): void
    {
        $container = $this->getContainerBuilder();
        (new ItspireFrameworkExtraExtension())->load($this->getConfigs(false), $container);

        static::assertTrue(condition: $container->hasParameter(Configuration::ALLOW_HTML_RESPONSE_PARAMETER));
        static::assertFalse(condition: $container->getParameter(Configuration::ALLOW_HTML_RESPONSE_PARAMETER));
    }

    /** @throws \Exception */
    #[Test]
    public function loadWithDefaultAllowHtmlResponseContentTypeTest(): void
    {
        $container = $this->getContainerBuilder();
        (new ItspireFrameworkExtraExtension())->load($this->getConfigs(), $container);

        static::assertTrue(condition: $container->hasParameter(Configuration::ALLOW_HTML_RESPONSE_PARAMETER));
        static::assertFalse(condition: $container->getParameter(Configuration::ALLOW_HTML_RESPONSE_PARAMETER));
    }

    private function getContainerBuilder(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', true);
        $container->setParameter('kernel.project_dir', realpath(__DIR__ . '/../../../../../'));
        $container->set(
            id: 'event_dispatcher',
            service: $this->getMockBuilder(EventDispatcherInterface::class)->getMock()
        );
        $container->set(id: 'logger', service: $this->getMockBuilder(LoggerInterface::class)->getMock());

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
