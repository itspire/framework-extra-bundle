<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Attribute;

use Itspire\Common\Enum\Http\HttpMethod;
use Itspire\Common\Enum\Http\HttpResponseStatus;
use Symfony\Component\Routing\Annotation\Route as BaseRoute;

#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Route extends BaseRoute implements AttributeInterface
{
    public function __construct(
        string|array $path = null,
        ?string $name = null,
        array $requirements = [],
        array $options = [],
        array $defaults = [],
        ?string $host = null,
        array|string $methods = [],
        array|string $schemes = [],
        ?string $condition = null,
        ?int $priority = null,
        string $locale = null,
        string $format = null,
        bool $utf8 = null,
        bool $stateless = null,
        ?string $env = null,
        private ?HttpResponseStatus $responseStatus = null
    ) {
        parent::__construct(
            [],
            $path,
            $name,
            $requirements,
            $options,
            $defaults,
            $host,
            array_map(
                fn (mixed $method): string => ($method instanceof HttpMethod) ? $method->value : $method,
                (array) $methods
            ),
            $schemes,
            $condition,
            $priority,
            $locale,
            $format,
            $utf8,
            $stateless,
            $env
        );
    }

    public function getResponseStatus(): ?HttpResponseStatus
    {
        return $this->responseStatus;
    }
}
