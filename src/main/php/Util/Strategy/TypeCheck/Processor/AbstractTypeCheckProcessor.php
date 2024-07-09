<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck\Processor;

use Itspire\Exception\Definition\Http\HttpExceptionDefinition;
use Itspire\Exception\Http\HttpException;
use Itspire\FrameworkExtraBundle\Attribute\ParamAttributeInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractTypeCheckProcessor implements TypeCheckProcessorInterface
{
    public function __construct(protected readonly LoggerInterface $logger)
    {
    }

    public function supports(string $type): bool
    {
        return in_array($type, $this->getTypes(), true);
    }

    protected function throwUnexpectedType(
        ?ParamAttributeInterface $paramAttribute,
        Request $request,
        string $expectedTypes,
        mixed $value
    ): void {
        $this->logger->alert(
            vsprintf(
                format: 'Invalid value type %s provided for parameter %s on route %s : expected one of %s.',
                values: [
                    gettype($value),
                    $paramAttribute->name,
                    $request->attributes->get(key: '_route'),
                    $expectedTypes,
                ]
            )
        );

        throw new HttpException(HttpExceptionDefinition::HTTP_BAD_REQUEST);
    }
}
