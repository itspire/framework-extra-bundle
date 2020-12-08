<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Annotation;

abstract class AbstractAnnotation implements AnnotationInterface
{
    protected string $defaultProperty = 'value';

    public function __construct(array $configuration)
    {
        if (isset($configuration['value'])) {
            $configuration[$this->defaultProperty] = $configuration['value'];
            unset($configuration['value']);
        }

        foreach ($configuration as $key => $value) {
            $method = 'set' . str_replace('_', '', ucwords($key, '_'));
            if (!method_exists($this, $method)) {
                throw new \BadMethodCallException(
                    sprintf("Unknown property '%s' on annotation '%s'.", $key, static::class)
                );
            }
            $this->$method($value);
        }
    }
}
