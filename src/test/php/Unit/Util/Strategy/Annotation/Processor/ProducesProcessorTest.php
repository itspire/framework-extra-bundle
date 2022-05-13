<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Unit\Util\Strategy\Annotation\Processor;

use Itspire\FrameworkExtraBundle\Annotation\BodyParam;
use Itspire\FrameworkExtraBundle\Annotation\Produces;
use Itspire\FrameworkExtraBundle\Attribute\AttributeInterface;
use Itspire\FrameworkExtraBundle\Attribute\ParamAttributeInterface;
use Itspire\FrameworkExtraBundle\Tests\Unit\Util\Strategy\Attribute\Processor as AttributeProcessorTest;
use Itspire\FrameworkExtraBundle\Util\MimeTypeMatcherInterface;
use Itspire\FrameworkExtraBundle\Util\Strategy\Annotation\Processor\ProducesProcessor;
use Psr\Log\LoggerInterface;

/** @deprecated */
class ProducesProcessorTest extends AttributeProcessorTest\ProducesProcessorTest
{
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->mimeTypeMatcherMock = $this->getMockBuilder(MimeTypeMatcherInterface::class)->getMock();

        $this->producesProcessor = new ProducesProcessor($this->mimeTypeMatcherMock, false, $this->loggerMock);
    }

    protected function getBodyParam(): ParamAttributeInterface
    {
        return new BodyParam(name: 'param');
    }

    protected function getProduces(mixed $acceptableFormats = [], mixed $serializationGroups = []): AttributeInterface
    {
        return new Produces(
            acceptableFormats: $acceptableFormats,
            serializationGroups: $serializationGroups
        );
    }
}
