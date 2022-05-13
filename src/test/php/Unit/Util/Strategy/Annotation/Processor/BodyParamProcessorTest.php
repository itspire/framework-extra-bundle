<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Unit\Util\Strategy\Annotation\Processor;

use Itspire\FrameworkExtraBundle\Annotation\BodyParam;
use Itspire\FrameworkExtraBundle\Annotation\Consumes;
use Itspire\FrameworkExtraBundle\Attribute\ParamAttributeInterface;
use Itspire\FrameworkExtraBundle\Tests\Unit\Util\Strategy\Attribute\Processor as AttributeProcessorTest;
use Itspire\FrameworkExtraBundle\Util\Strategy\Annotation\Processor\BodyParamProcessor;
use Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck\TypeCheckHandlerInterface;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;

/** @deprecated */
class BodyParamProcessorTest extends AttributeProcessorTest\BodyParamProcessorTest
{
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->serializerMock = $this->getMockBuilder(SerializerInterface::class)->getMock();
        $this->typeCheckHandlerMock = $this->getMockBuilder(TypeCheckHandlerInterface::class)->getMock();

        $this->bodyParamProcessor = new BodyParamProcessor(
            $this->serializerMock,
            $this->loggerMock,
            $this->typeCheckHandlerMock
        );
    }

    public function supportsProvider(): array
    {
        return [
            'notSupported' => [new Consumes([]), false],
            'supported' => [new BodyParam(name: 'param'), true],
        ];
    }

    protected function getBodyParam(string $classFqn): ParamAttributeInterface
    {
        return new BodyParam(name: 'param', type: 'class', class: $classFqn);
    }
}
