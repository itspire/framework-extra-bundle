<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck\Processor;

use Itspire\Exception\Definition\Http\HttpExceptionDefinition;
use Itspire\Exception\Http\HttpException;
use Itspire\FrameworkExtraBundle\Annotation\ParamInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractTypeCheckProcessor implements TypeCheckProcessorInterface
{
    protected ?LoggerInterface $logger = null;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function supports(string $type): bool
    {
        return in_array($type, $this->getTypes(), true);
    }

    /** @param mixed $value */
    protected function throwUnexpectedType(
        ParamInterface $annotation,
        Request $request,
        string $expectedTypes,
        $value
    ): void {
        $this->logger->alert(
            sprintf(
                'Invalid value type %s provided for parameter %s on route %s : expected one of %s.',
                gettype($value),
                $annotation->getName(),
                $request->attributes->get('_route'),
                $expectedTypes
            )
        );

        throw new HttpException(
            new HttpExceptionDefinition(HttpExceptionDefinition::HTTP_BAD_REQUEST)
        );
    }
}
