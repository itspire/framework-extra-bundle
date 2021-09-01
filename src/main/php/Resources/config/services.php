<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

use Itspire\FrameworkExtraBundle\EventListener\ControllerListener;
use Itspire\FrameworkExtraBundle\EventListener\ErrorListener;
use Itspire\FrameworkExtraBundle\EventListener\ViewListener;
use Itspire\FrameworkExtraBundle\Util\Strategy\Annotation\AnnotationHandler;
use Itspire\FrameworkExtraBundle\Util\Strategy\Annotation\Processor\AnnotationProcessorInterface;
use Itspire\FrameworkExtraBundle\Util\Strategy\Annotation\Processor\ProducesProcessor;
use Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck\Processor\TypeCheckProcessorInterface;
use Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck\TypeCheckHandler;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $configurator) {

    $services = $configurator->services()->defaults()->autowire()->autoconfigure();

    $services->instanceof(AnnotationProcessorInterface::class)->tag('itspire.framework_extra.annotation_processor');
    $services->instanceof(TypeCheckProcessorInterface::class)->tag('itspire.framework_extra.type_checker_processor');

    $services->load(
        'Itspire\\Exception\\',
        '%kernel.project_dir%/vendor/itspire/exceptions/src/main/php/*'
    );

    $services->load('Itspire\\FrameworkExtraBundle\\Util\\', '../../Util');

    $services->load('Itspire\\FrameworkExtraBundle\\EventListener\\', '../../EventListener');

    $services
        ->set(ProducesProcessor::class)
        ->bind('$allowHTMLResponseContentType', '%itspire.framework_extra.allow_html_response_content_type%');

    $services
        ->set(AnnotationHandler::class)
        ->bind(
            '$annotationProcessors',
            Configurator\tagged_iterator('itspire.framework_extra.annotation_processor')
        );

    $services
        ->set(TypeCheckHandler::class)
        ->bind(
            '$typeCheckProcessors',
            Configurator\tagged_iterator('itspire.framework_extra.type_checker_processor')
        );

    $services
        ->set(ControllerListener::class)
        ->tag(
            'kernel.event_listener',
            ['event' => 'kernel.controller', 'method' => 'onKernelController', 'priority' => 5]
        );

    $services
        ->set(ErrorListener::class)
        ->bind(
            '$exceptionApiMappers',
            Configurator\tagged_iterator('itspire.framework_extra.exception_api_mapper')
        )
        ->bind(
            '$exceptionApiAdapters',
            Configurator\tagged_iterator('itspire.framework_extra.exception_api_adapter')
        )
        ->tag(
            'kernel.event_listener',
            ['event' => 'kernel.exception', 'method' => 'onKernelException', 'priority' => 5]
        );

    $services
        ->set(ViewListener::class)
        ->tag(
            'kernel.event_listener',
            ['event' => 'kernel.view', 'method' => 'onKernelView', 'priority' => 5]
        );
};
