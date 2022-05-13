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
use Itspire\FrameworkExtraBundle\Attribute\AttributeInterface;
use Itspire\FrameworkExtraBundle\Tests\Unit\Util\Strategy\Attribute\Processor as AttributeProcessorTest;
use Itspire\FrameworkExtraBundle\Util\MimeTypeMatcherInterface;
use Itspire\FrameworkExtraBundle\Util\Strategy\Annotation\Processor\ConsumesProcessor;
use Psr\Log\LoggerInterface;

/** @deprecated */
class ConsumesProcessorTest extends AttributeProcessorTest\ConsumesProcessorTest
{
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->mimeTypeMatcherMock = $this->getMockBuilder(MimeTypeMatcherInterface::class)->getMock();

        $this->consumesProcessor = new ConsumesProcessor($this->mimeTypeMatcherMock, $this->loggerMock);
    }

    public function supportsProvider(): array
    {
        return [
            'notSupported' => [new BodyParam(name: 'param'), false],
            'supported' => [new Consumes([]), true],
        ];
    }

    protected function getConsumes(
        mixed $consumableContentTypes = [],
        mixed $deserializationGroups = []
    ): AttributeInterface {
        return new Consumes(
            consumableContentTypes: $consumableContentTypes,
            deserializationGroups: $deserializationGroups
        );
    }
}
