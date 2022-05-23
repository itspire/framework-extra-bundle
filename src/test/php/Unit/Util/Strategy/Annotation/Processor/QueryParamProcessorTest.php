<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Unit\Util\Strategy\Annotation\Processor;

use Itspire\FrameworkExtraBundle\Annotation\Consumes;
use Itspire\FrameworkExtraBundle\Annotation\QueryParam;
use Itspire\FrameworkExtraBundle\Attribute\ParamAttributeInterface;
use Itspire\FrameworkExtraBundle\Tests\Unit\Util\Strategy\Attribute\Processor as AttributeProcessorTest;
use Itspire\FrameworkExtraBundle\Util\Strategy\Annotation\Processor\QueryParamProcessor;
use Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck\TypeCheckHandlerInterface;
use Psr\Log\LoggerInterface;

/** @deprecated */
class QueryParamProcessorTest extends AttributeProcessorTest\QueryParamProcessorTest
{
    protected function setUp(): void
    {
        $this->typeCheckHandlerMock = $this->getMockBuilder(TypeCheckHandlerInterface::class)->getMock();

        $this->queryParamProcessor = new QueryParamProcessor(
            $this->typeCheckHandlerMock,
            $this->getMockBuilder(LoggerInterface::class)->getMock()
        );
    }

    public function supportsProvider(): array
    {
        return [
            'notSupported' => [new Consumes([]), false],
            'supported' => [new QueryParam(name: 'param'), true],
        ];
    }

    protected function getQueryParam(bool $required, mixed $default = null): ParamAttributeInterface
    {
        return new QueryParam(name: 'param', type: 'int', required: $required, requirements: '\d+', default: $default);
    }
}
