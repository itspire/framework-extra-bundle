<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Util\Strategy\Annotation\Processor;

use Itspire\FrameworkExtraBundle\Annotation\AnnotationInterface;
use Itspire\FrameworkExtraBundle\Annotation\Route;
use Itspire\FrameworkExtraBundle\Configuration\CustomRequestAttributes;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class RouteProcessor extends AbstractAnnotationProcessor
{
    public function supports(AnnotationInterface $annotation): bool
    {
        return $annotation instanceof Route;
    }

    /** @param Route $annotation */
    protected function handleProcess(ControllerEvent $event, AnnotationInterface $annotation): void
    {
        $request = $event->getRequest();
        $request->attributes->set(CustomRequestAttributes::ROUTE_CALLED, true);
        if (null !== $annotation->getResponseStatusCode()) {
            $request->attributes->set(
                CustomRequestAttributes::RESPONSE_STATUS_CODE,
                $annotation->getResponseStatusCode()
            );
        }
    }
}
