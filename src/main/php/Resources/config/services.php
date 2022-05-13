<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

use Itspire\FrameworkExtraBundle\EventListener\ControllerListener;
use Itspire\FrameworkExtraBundle\EventListener\ErrorListener;
use Itspire\FrameworkExtraBundle\EventListener\ViewListener;
use Itspire\FrameworkExtraBundle\Util\Strategy\Annotation as ItspireFrameworkAnnotation;
use Itspire\FrameworkExtraBundle\Util\Strategy\Attribute as ItspireFrameworkAttribute;
use Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck\Processor\TypeCheckProcessorInterface;
use Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck\TypeCheckHandler;
use Symfony\Component\DependencyInjection\Loader\Configurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $configurator) {

    $services = $configurator->services()->defaults()->autowire()->autoconfigure();

    $services
        ->instanceof(fqcn: ItspireFrameworkAnnotation\Processor\AnnotationProcessorInterface::class)
        ->tag(name: 'itspire.framework_extra.annotation_processor');

    $services
        ->instanceof(fqcn: ItspireFrameworkAttribute\Processor\AttributeProcessorInterface::class)
        ->tag(name: 'itspire.framework_extra.attribute_processor');

    $services
        ->instanceof(fqcn: TypeCheckProcessorInterface::class)
        ->tag(name: 'itspire.framework_extra.type_checker_processor');

    $services->load(
        namespace: 'Itspire\\Exception\\',
        resource: '%kernel.project_dir%/vendor/itspire/exceptions/src/main/php/*'
    );

    $services->load(namespace: 'Itspire\\FrameworkExtraBundle\\Util\\', resource: '../../Util');

    $services->load(namespace: 'Itspire\\FrameworkExtraBundle\\EventListener\\', resource: '../../EventListener');

    $services
        ->set(id: ItspireFrameworkAnnotation\Processor\ProducesProcessor::class)
        ->bind(
            nameOrFqcn: '$allowHTMLResponseContentType',
            valueOrRef: '%itspire.framework_extra.allow_html_response_content_type%'
        );

    $services
        ->set(id: ItspireFrameworkAttribute\Processor\ProducesProcessor::class)
        ->bind(
            nameOrFqcn: '$allowHTMLResponseContentType',
            valueOrRef: '%itspire.framework_extra.allow_html_response_content_type%'
        );

    $services
        ->set(id: ItspireFrameworkAnnotation\AnnotationHandler::class)
        ->bind(
            nameOrFqcn: '$processors',
            valueOrRef: Configurator\tagged_iterator(tag: 'itspire.framework_extra.annotation_processor')
        );

    $services
        ->set(id: ItspireFrameworkAttribute\AttributeHandler::class)
        ->bind(
            nameOrFqcn: '$processors',
            valueOrRef: Configurator\tagged_iterator(tag: 'itspire.framework_extra.attribute_processor')
        );

    $services
        ->set(id: TypeCheckHandler::class)
        ->bind(
            nameOrFqcn: '$processors',
            valueOrRef: Configurator\tagged_iterator(tag: 'itspire.framework_extra.type_checker_processor')
        );

    $services->alias(
        id: ItspireFrameworkAttribute\AttributeHandlerInterface::class,
        referencedId: ItspireFrameworkAttribute\AttributeHandler::class
    );

    $services
        ->set(ControllerListener::class)
        ->tag(
            name: 'kernel.event_listener',
            attributes: ['event' => 'kernel.controller', 'method' => 'onKernelController', 'priority' => 5]
        );

    $services
        ->set(id: ErrorListener::class)
        ->bind(
            nameOrFqcn: '$exceptionApiMappers',
            valueOrRef: Configurator\tagged_iterator(tag: 'itspire.framework_extra.exception_api_mapper')
        )
        ->bind(
            nameOrFqcn: '$exceptionApiAdapters',
            valueOrRef: Configurator\tagged_iterator(tag: 'itspire.framework_extra.exception_api_adapter')
        )
        ->tag(
            name: 'kernel.event_listener',
            attributes: ['event' => 'kernel.exception', 'method' => 'onKernelException', 'priority' => 5]
        );

    $services
        ->set(id: ViewListener::class)
        ->tag(
            name: 'kernel.event_listener',
            attributes: ['event' => 'kernel.view', 'method' => 'onKernelView', 'priority' => 5]
        );
};
