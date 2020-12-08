<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Unit\DependencyInjection;

use Itspire\FrameworkExtraBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    /** @test */
    public function getConfigTreeBuilderWithInvalidAllowHtmlResponseContentTypeTest(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $config = [
            'itspire_framework_extra' => [
                'allow_html_response_content_type' => 'false',
            ]
        ];

        $processor = new Processor();
        $processor->processConfiguration(new Configuration(), $config);
    }

    /** @test */
    public function getConfigTreeBuilderWithAllowedHtmlResponseContentTypeTest(): void
    {
        $config = [
            'itspire_framework_extra' => [
                'allow_html_response_content_type' => true,
            ]
        ];

        $processor = new Processor();
        $processedConfig = $processor->processConfiguration(new Configuration(), $config);

        static::assertTrue($processedConfig['allow_html_response_content_type']);
    }

    /** @test */
    public function getConfigTreeBuilderWithDisallowedHtmlResponseContentTypeTest(): void
    {
        $config = [
            'itspire_framework_extra' => [
                'allow_html_response_content_type' => false,
            ]
        ];

        $processor = new Processor();
        $processedConfig = $processor->processConfiguration(new Configuration(), $config);

        static::assertFalse($processedConfig['allow_html_response_content_type']);
    }
}
