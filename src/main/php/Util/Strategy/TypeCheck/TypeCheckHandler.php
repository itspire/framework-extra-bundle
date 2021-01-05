<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck;

use Itspire\Exception\Http\Definition\HttpExceptionDefinition;
use Itspire\Exception\Http\HttpException;
use Itspire\FrameworkExtraBundle\Annotation\ParamInterface;
use Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck\Processor\TypeCheckProcessorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class TypeCheckHandler implements TypeCheckHandlerInterface
{
    protected ?LoggerInterface $logger = null;

    /** @var TypeCheckProcessorInterface[] */
    private array $processors = [];

    public function __construct(LoggerInterface $logger, iterable $typeCheckProcessors = [])
    {
        $this->logger = $logger;

        foreach ($typeCheckProcessors as $typeCheckProcessor) {
            $this->registerProcessor($typeCheckProcessor);
        }
    }

    public function registerProcessor(TypeCheckProcessorInterface $typeCheckProcessor): self
    {
        $typeCheckProcessorClass = get_class($typeCheckProcessor);

        if (false === array_key_exists($typeCheckProcessorClass, $this->processors)) {
            $this->processors[$typeCheckProcessorClass] = $typeCheckProcessor;
        }

        return $this;
    }

    /** @return mixed */
    public function process(ParamInterface $annotation, Request $request, $value)
    {
        if (null === $annotation->getType()) {
            return $value;
        }

        if (in_array($value, ['', null], true) && false === $annotation->isRequired()) {
            return $value;
        }

        foreach ($this->processors as $processor) {
            if (false === $processor->supports($annotation->getType())) {
                continue;
            }

            return $processor->process($annotation, $request, $value);
        }

        $this->logger->error(
            sprintf(
                'No processor found to check value of expected type %s in annotation %s on route %s.',
                $annotation->getType(),
                $annotation->getName(),
                $request->attributes->get('_route')
            )
        );

        throw new HttpException(
            new HttpExceptionDefinition(HttpExceptionDefinition::HTTP_INTERNAL_SERVER_ERROR)
        );
    }
}
