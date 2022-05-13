<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Unit\Util\Strategy\Annotation\Processor;

use Itspire\Common\Enum\Http\HttpResponseStatus;
use Itspire\FrameworkExtraBundle\Annotation\Consumes;
use Itspire\FrameworkExtraBundle\Annotation\Security;
use Itspire\FrameworkExtraBundle\Attribute\AttributeInterface;
use Itspire\FrameworkExtraBundle\Tests\Unit\Util\Strategy\Attribute\Processor as AttributeProcessorTest;
use Itspire\FrameworkExtraBundle\Util\Strategy\Annotation\Processor\SecurityProcessor;
use Psr\Log\LoggerInterface;

/** @deprecated */
class SecurityProcessorTest extends AttributeProcessorTest\SecurityProcessorTest
{
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $this->securityProcessor = new SecurityProcessor($this->loggerMock);
    }

    public function supportsProvider(): array
    {
        return [
            'notSupported' => [new Consumes([]), false],
            'supported' => [new Security(expression: 'true'), true],
        ];
    }

    protected function getSecurity(): AttributeInterface
    {
        return new Security(expression: 'false', responseStatus: HttpResponseStatus::HTTP_FORBIDDEN->value);
    }
}
