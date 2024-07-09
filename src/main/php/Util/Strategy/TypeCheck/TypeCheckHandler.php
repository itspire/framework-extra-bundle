<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck;

use Itspire\Exception\Definition\Http\HttpExceptionDefinition;
use Itspire\Exception\Http\HttpException;
use Itspire\FrameworkExtraBundle\Attribute\AbstractParamAttribute;
use Itspire\FrameworkExtraBundle\Attribute\ParamAttributeInterface;
use Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck\Processor\TypeCheckProcessorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class TypeCheckHandler implements TypeCheckHandlerInterface
{
    /** @var TypeCheckProcessorInterface[] */
    private array $processors = [];

    public function __construct(protected readonly LoggerInterface $logger, iterable $processors = [])
    {
        foreach ($processors as $processor) {
            $this->registerProcessor($processor);
        }
    }

    public function registerProcessor(TypeCheckProcessorInterface $typeCheckProcessor): self
    {
        if (false === array_key_exists($typeCheckProcessor::class, $this->processors)) {
            $this->processors[$typeCheckProcessor::class] = $typeCheckProcessor;
        }

        return $this;
    }

    public function process(AbstractParamAttribute $paramAttribute, Request $request, mixed $value): mixed
    {
        if (null === $paramAttribute->type) {
            return $value;
        }

        if (in_array($value, ['', null], true) && false === $paramAttribute->required) {
            return $value;
        }

        foreach ($this->processors as $processor) {
            if (false === $processor->supports($paramAttribute->type)) {
                continue;
            }

            return $processor->process($paramAttribute, $request, $value);
        }

        $this->logger->error(
            vsprintf(
                format: 'No processor found to check expected value type "%s" for param "%s" on route "%s".',
                values: [
                    $paramAttribute->type,
                    $paramAttribute->name,
                    $request->attributes->get(key: '_route'),
                ]
            )
        );

        throw new HttpException(HttpExceptionDefinition::HTTP_INTERNAL_SERVER_ERROR);
    }
}
