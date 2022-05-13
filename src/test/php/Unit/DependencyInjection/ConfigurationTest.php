<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
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

        $configs = [
            'itspire_framework_extra' => [
                'allow_html_response_content_type' => 'false',
            ]
        ];

        (new Processor())->processConfiguration(new Configuration(), $configs);
    }

    /** @test */
    public function getConfigTreeBuilderWithAllowedHtmlResponseContentTypeTest(): void
    {
        $configs = [
            'itspire_framework_extra' => [
                'allow_html_response_content_type' => true,
            ]
        ];

        $processedConfig = (new Processor())->processConfiguration(new Configuration(), $configs);

        static::assertTrue(condition: $processedConfig['allow_html_response_content_type']);
    }

    /** @test */
    public function getConfigTreeBuilderWithDisallowedHtmlResponseContentTypeTest(): void
    {
        $configs = [
            'itspire_framework_extra' => [
                'allow_html_response_content_type' => false,
            ]
        ];

        $processedConfig = (new Processor())->processConfiguration(new Configuration(), $configs);

        static::assertFalse(condition: $processedConfig['allow_html_response_content_type']);
    }
}
