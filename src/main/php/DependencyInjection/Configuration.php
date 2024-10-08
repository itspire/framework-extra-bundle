<?php

/*
 * Copyright (c) 2016 - 2024 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const ALLOW_HTML_RESPONSE_PARAMETER = 'itspire.framework_extra.allow_html_response_content_type';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(name: 'itspire_framework_extra');
        $treeBuilder
            ->getRootNode()
                ->children()
                    ->booleanNode(name: 'allow_html_response_content_type')
                    ->defaultValue(false)
                    ->validate()
                        ->ifTrue(fn (mixed $v): bool => !is_bool($v))
                        ->thenInvalid(message: 'allow_html_response_content_type can only be true or false')
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
