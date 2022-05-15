<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Util\Strategy\Attribute\Processor;

use Itspire\Exception\Definition\Http\HttpExceptionDefinition;
use Itspire\Exception\Http\HttpException;
use Itspire\FrameworkExtraBundle\Attribute\AttributeInterface;
use Itspire\FrameworkExtraBundle\Attribute\BodyParam;
use Itspire\FrameworkExtraBundle\Attribute\FileParam;
use Itspire\FrameworkExtraBundle\Attribute\HeaderParam;
use Itspire\FrameworkExtraBundle\Attribute\ParamAttributeInterface;
use Itspire\FrameworkExtraBundle\Attribute\QueryParam;
use Itspire\FrameworkExtraBundle\Attribute\RequestParam;
use Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck\TypeCheckHandlerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

abstract class AbstractParamAttributeProcessor extends AbstractAttributeProcessor
{
    public function __construct(protected TypeCheckHandlerInterface $typeCheckHandler, LoggerInterface $logger)
    {
        parent::__construct($logger);
    }

    /** @param ParamAttributeInterface $attribute */
    protected function handleProcess(ControllerEvent $event, AttributeInterface $attribute): void
    {
        $this->checkForConflictingNames($event->getRequest(), $attribute);

        $reflectionParameter = $this->findMatchingMethodParameter($attribute->getName(), $event->getController());

        // If native type is provided on the method, its definition takes precedence over the one on the attribute
        if (null !== $reflectionParameter->getType()) {
            $attribute
                ->setType($reflectionParameter->getType()->getName())
                ->setRequired(!$reflectionParameter->getType()->allowsNull());
        }

        // If native default value is provided, it will take precedence over the one on the attribute
        if ($reflectionParameter->isDefaultValueAvailable()) {
            $attribute->setDefault($reflectionParameter->getDefaultValue());
        }

        $paramValue = $this->getParamValue($event->getRequest(), $attribute);

        $this->validateValue($attribute, $event, $paramValue);
    }

    protected function getParamValue(Request $request, ParamAttributeInterface $attribute): mixed
    {
        return match ($attribute::class) {
            BodyParam::class => $request->getContent() ?: null,
            FileParam::class => $request->files->get(key: $attribute->getName(), default: null),
            HeaderParam::class => $request->headers->get(
                key: $attribute->getHeaderName(),
                default: $attribute->getDefault()
            ),
            QueryParam::class => $request->query->has($attribute->getName())
                ? $request->query->all()[$attribute->getName()]
                : $attribute->getDefault(),
            RequestParam::class => $request->request->has($attribute->getName())
                ? $request->request->all()[$attribute->getName()]
                : $attribute->getDefault(),
        };
    }

    private function checkForConflictingNames(Request $request, ParamAttributeInterface $attribute): void
    {
        if (true === $request->attributes->has($attribute->getName())) {
            $this->logger->error(
                vsprintf(
                    format: 'Name conflict detected for parameter %s in route %s.',
                    values: [$attribute->getName(), $request->attributes->get(key: '_route')]
                )
            );

            throw new HttpException(HttpExceptionDefinition::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function validateValue(ParamAttributeInterface $attribute, ControllerEvent $event, mixed $value): void
    {
        $this->checkMissingValue($attribute, $event->getRequest(), $value);

        if (!$attribute instanceof FileParam && !$attribute instanceof BodyParam) {
            $value = $this->typeCheckHandler->process($attribute, $event->getRequest(), $value);
            $this->checkParamRequirements($attribute, $value);
        }

        $event->getRequest()->attributes->set($attribute->getName(), $value);
    }

    private function findMatchingMethodParameter(string $paramName, callable $controller): \ReflectionParameter
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $reflectionClass = new \ReflectionClass(get_class($controller[0]));

        /** @noinspection PhpUnhandledExceptionInspection */
        $reflectionMethod = $reflectionClass->getMethod($controller[1]);

        /** @var \ReflectionParameter $reflectionParameter */
        foreach ($reflectionMethod->getParameters() as $reflectionParameter) {
            if ($reflectionParameter->getName() === $paramName) {
                return $reflectionParameter;
            }
        }

        $this->logger->error(
            vsprintf(
                format: 'Parameter %s does not exist on method %s::%s',
                values: [$paramName, $reflectionClass->getName(), $reflectionMethod->getName()]
            )
        );

        throw new HttpException(HttpExceptionDefinition::HTTP_INTERNAL_SERVER_ERROR);
    }

    private function checkMissingValue(ParamAttributeInterface $attribute, Request $request, mixed $value): void
    {
        if (in_array($value, ['', null], true) && true === $attribute->isRequired()) {
            $this->logger->alert(
                vsprintf(
                    format: '"%s" defined on route "%s" has no matching "%s" parameter in the request.',
                    values: [$attribute::class, $request->attributes->get(key: '_route'), $attribute->getName()]
                )
            );

            throw new HttpException(HttpExceptionDefinition::HTTP_BAD_REQUEST);
        }
    }

    private function checkParamRequirements(ParamAttributeInterface $attribute, mixed $value): void
    {
        if (is_array($value)) {
            $self = $this;
            array_walk(
                $value,
                function ($data) use ($attribute, $self) {
                    $self->checkParamRequirements($attribute, $data);
                }
            );
        } elseif (
            null !== $value
            && null !== $attribute->getRequirements()
            && !preg_match('#^(' . $attribute->getRequirements() . ')$#xs', (string) $value)
        ) {
            $this->logger->alert(
                vsprintf(
                    format: 'Parameter value for %s does not match defined requirement %s.',
                    values: [$attribute->getName(), $attribute->getRequirements()]
                )
            );

            throw new HttpException(HttpExceptionDefinition::HTTP_BAD_REQUEST);
        }
    }
}
