<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Unit\Util\Strategy\Annotation\Processor;

use Itspire\FrameworkExtraBundle\Annotation\Consumes;
use Itspire\FrameworkExtraBundle\Annotation\RequestParam;
use Itspire\FrameworkExtraBundle\Attribute\ParamAttributeInterface;
use Itspire\FrameworkExtraBundle\Tests\Unit\Util\Strategy\Attribute\Processor as AttributeProcessorTest;
use Itspire\FrameworkExtraBundle\Util\Strategy\Annotation\Processor\RequestParamProcessor;
use Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck\TypeCheckHandlerInterface;
use Psr\Log\LoggerInterface;

/** @deprecated */
class RequestParamProcessorTest extends AttributeProcessorTest\RequestParamProcessorTest
{
    protected function setUp(): void
    {
        $this->typeCheckHandlerMock = $this->getMockBuilder(TypeCheckHandlerInterface::class)->getMock();

        $this->requestParamProcessor = new RequestParamProcessor(
            $this->typeCheckHandlerMock,
            $this->getMockBuilder(LoggerInterface::class)->getMock()
        );
    }

    public function supportsProvider(): array
    {
        return [
            'notSupported' => [new Consumes([]), false],
            'supported' => [new RequestParam(name: 'param'), true],
        ];
    }

    protected function getRequestParam(): ParamAttributeInterface
    {
        return new RequestParam(name: 'param', type: 'int', required: true, requirements: '\d+');
    }
}
