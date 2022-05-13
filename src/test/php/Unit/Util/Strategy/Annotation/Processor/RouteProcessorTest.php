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
use Itspire\FrameworkExtraBundle\Annotation\Route;
use Itspire\FrameworkExtraBundle\Attribute\AttributeInterface;
use Itspire\FrameworkExtraBundle\Tests\Unit\Util\Strategy\Attribute\Processor as AttributeProcessorTest;
use Itspire\FrameworkExtraBundle\Util\Strategy\Annotation\Processor\RouteProcessor;
use Psr\Log\LoggerInterface;

/** @deprecated */
class RouteProcessorTest extends AttributeProcessorTest\RouteProcessorTest
{
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $this->routeProcessor = new RouteProcessor($this->loggerMock);
    }

    public function supportsProvider(): array
    {
        return [
            'notSupported' => [new Consumes([]), false],
            'supported' => [new Route(path: '/'), true],
        ];
    }

    protected function getConsumes(): AttributeInterface
    {
        return new Consumes([]);
    }

    protected function getRoute(): AttributeInterface
    {
        return new Route(path: '/test', responseStatus: HttpResponseStatus::HTTP_OK);
    }
}
