<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Util\Strategy\Annotation\Processor;

use Itspire\Exception\Http\Definition\HttpExceptionDefinition;
use Itspire\Exception\Http\HttpException;
use Itspire\FrameworkExtraBundle\Annotation\AnnotationInterface;
use Itspire\FrameworkExtraBundle\Annotation\BodyParam;
use Itspire\FrameworkExtraBundle\Annotation\FileParam;
use Itspire\FrameworkExtraBundle\Annotation\HeaderParam;
use Itspire\FrameworkExtraBundle\Annotation\ParamInterface;
use Itspire\FrameworkExtraBundle\Annotation\QueryParam;
use Itspire\FrameworkExtraBundle\Annotation\RequestParam;
use Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck\TypeCheckHandlerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

abstract class AbstractParamAnnotationProcessor extends AbstractAnnotationProcessor
{
    protected ?TypeCheckHandlerInterface $typeCheckHandler = null;

    public function __construct(LoggerInterface $logger, TypeCheckHandlerInterface $typeCheckHandler)
    {
        parent::__construct($logger);
        $this->typeCheckHandler = $typeCheckHandler;
    }

    /** @param ParamInterface $annotation */
    protected function handleProcess(ControllerEvent $event, AnnotationInterface $annotation): void
    {
        $this->checkForConflictingNames($event->getRequest(), $annotation);

        $reflectionParameter = $this->findMatchingMethodParameter($annotation->getName(), $event->getController());

        // If native type is provided on the method, its definition takes precedence over the one on the annotation
        if (null !== $reflectionParameter->getType()) {
            $annotation
                ->setType($reflectionParameter->getType()->getName())
                ->setRequired(!$reflectionParameter->getType()->allowsNull());
        }

        $paramValue = $this->getParamValue($event->getRequest(), $annotation);

        $this->validateValue($paramValue, $annotation, $event);
    }

    /** @return mixed|null */
    protected function getParamValue(Request $request, ParamInterface $annotation)
    {
        if ($annotation instanceof BodyParam) {
            return $request->getContent();
        }

        $paramValueLocations = [
            FileParam::class => 'files',
            HeaderParam::class => 'headers',
            QueryParam::class => 'query',
            RequestParam::class => 'request',
        ];

        $paramValueLocation = $paramValueLocations[get_class($annotation)];
        $paramValueName = ($annotation instanceof HeaderParam)
            ? $annotation->getHeaderName()
            : $annotation->getName();

        if ($request->{$paramValueLocation}->has($paramValueName)) {
            return ('array' === $annotation->getType())
                ? $request->{$paramValueLocation}->all($paramValueName)
                : $request->{$paramValueLocation}->get($paramValueName);
        }

        return null;
    }

    protected function checkForConflictingNames(Request $request, ParamInterface $annotation): void
    {
        if (true === $request->attributes->has($annotation->getName())) {
            $this->logger->error(
                sprintf(
                    'Name conflict detected for parameter %s in route %s.',
                    $annotation->getName(),
                    $request->attributes->get('_route')
                )
            );

            throw new HttpException(
                new HttpExceptionDefinition(HttpExceptionDefinition::HTTP_INTERNAL_SERVER_ERROR)
            );
        }
    }

    protected function validateValue($value, ParamInterface $annotation, ControllerEvent $event): void
    {
        $this->checkMissingValue($value, $annotation, $event->getRequest());

        if (!$annotation instanceof FileParam && !$annotation instanceof BodyParam) {
            $value = $this->typeCheckHandler->process($annotation, $event->getRequest(), $value);
            $this->checkParamRequirements($annotation, $value);
        }

        $event->getRequest()->attributes->set($annotation->getName(), $value);
    }

    protected function findMatchingMethodParameter(string $paramName, callable $controller): \ReflectionParameter
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
            sprintf(
                'Parameter %s does not exist on method %s::%s',
                $paramName,
                $reflectionClass->getName(),
                $reflectionMethod->getName()
            )
        );

        throw new HttpException(
            new HttpExceptionDefinition(HttpExceptionDefinition::HTTP_INTERNAL_SERVER_ERROR)
        );
    }

    /** @param \ReflectionParameter[] $reflectionParameters */
    private function checkMissingValue($value, ParamInterface $annotation, Request $request): void
    {
        if (in_array($value, ['', null], true) && true === $annotation->isRequired()) {
            $this->logger->alert(
                sprintf(
                    '@%s annotation is defined on route %s but the corresponding value was not in the request.',
                    $this->getAnnotationName($annotation),
                    $request->attributes->get('_route')
                )
            );

            throw new HttpException(
                new HttpExceptionDefinition(HttpExceptionDefinition::HTTP_BAD_REQUEST)
            );
        }
    }

    /** @param mixed $value */
    private function checkParamRequirements(ParamInterface $annotation, $value): void
    {
        if (is_array($value)) {
            $self = $this;
            array_walk(
                $value,
                function ($data) use ($annotation, $self) {

                    $self->checkParamRequirements($annotation, $data);
                }
            );
        } elseif (
            null !== $value
            && null !== $annotation->getRequirements()
            && !preg_match('#^(' . $annotation->getRequirements() . ')$#xs', (string) $value)
        ) {
            $this->logger->alert(
                sprintf(
                    'Parameter value for %s does not match defined requirement %s.',
                    $annotation->getName(),
                    $annotation->getRequirements()
                )
            );

            throw new HttpException(
                new HttpExceptionDefinition(HttpExceptionDefinition::HTTP_BAD_REQUEST)
            );
        }
    }
}
