<?php

/*
 * Copyright (c) 2016 - 2024 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Unit\Util\Strategy\Attribute\Processor;

use Itspire\Common\Enum\Http\HttpResponseStatus;
use Itspire\FrameworkExtraBundle\Attribute\Consumes;
use Itspire\FrameworkExtraBundle\Attribute\Security;
use Itspire\FrameworkExtraBundle\Tests\Unit\Fixtures\FixtureController;
use Itspire\FrameworkExtraBundle\Util\Strategy\Attribute\Processor\SecurityProcessor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class SecurityProcessorTest extends TestCase
{
    protected MockObject | LoggerInterface | null $loggerMock = null;
    protected ?SecurityProcessor $securityProcessor = null;

    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $this->securityProcessor = new SecurityProcessor($this->loggerMock);
    }

    protected function tearDown(): void
    {
        unset($this->securityProcessor, $this->loggerMock);
    }

    public static function supportsProvider(): array
    {
        return [
            'notSupported' => [new Consumes(), false],
            'supported' => [new Security(expression: 'true'), true],
        ];
    }

    #[Test]
    #[DataProvider('supportsProvider')]
    public function supportsTest($attribute, $result): void
    {
        static::assertEquals(expected: $result, actual: $this->securityProcessor->supports($attribute));
    }

    #[Test]
    public function processTest(): void
    {
        $httpResponseStatus = HttpResponseStatus::HTTP_FORBIDDEN;
        $attribute = $this->getSecurity();

        $this->securityProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'param'],
                new Request(),
                HttpKernelInterface::MAIN_REQUEST
            ),
            $attribute
        );

        static::assertEquals(expected: $httpResponseStatus->value, actual: $attribute->getStatusCode());
        static::assertEquals(expected: $httpResponseStatus->getDescription(), actual: $attribute->getMessage());
    }

    protected function getSecurity(): Security
    {
        return new Security(expression: 'false', responseStatus: HttpResponseStatus::HTTP_FORBIDDEN);
    }
}
