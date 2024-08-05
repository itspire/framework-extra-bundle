<?php

/*
 * Copyright (c) 2016 - 2024 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Unit\Util\Strategy\Attribute\Processor;

use Itspire\FrameworkExtraBundle\Attribute\AttributeInterface;
use Itspire\FrameworkExtraBundle\Attribute\Consumes;
use Itspire\FrameworkExtraBundle\Attribute\IsGranted;
use Itspire\FrameworkExtraBundle\Tests\Unit\Fixtures\FixtureController;
use Itspire\FrameworkExtraBundle\Tests\Unit\Fixtures\FixturePermission;
use Itspire\FrameworkExtraBundle\Util\Strategy\Attribute\Processor\IsGrantedProcessor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class IsGrantedProcessorTest extends TestCase
{
    protected MockObject | LoggerInterface | null $loggerMock = null;
    protected ?IsGrantedProcessor $isGrantedProcessor = null;

    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $this->isGrantedProcessor = new IsGrantedProcessor($this->loggerMock);
    }

    protected function tearDown(): void
    {
        unset($this->isGrantedProcessor, $this->loggerMock);
    }

    public static function supportsProvider(): array
    {
        return [
            'notSupported' => [new Consumes(), false],
            'supported' => [new IsGranted(data: 'TEST'), true],
        ];
    }

    #[Test]
    #[DataProvider('supportsProvider')]
    public function supportsTest($attribute, $result): void
    {
        static::assertEquals(expected: $result, actual: $this->isGrantedProcessor->supports($attribute));
    }

    #[Test]
    public function processTest(): void
    {
        $fixturePermission = FixturePermission::TEST;
        $attribute = $this->getIsGranted();

        $this->isGrantedProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'param'],
                new Request(),
                HttpKernelInterface::MAIN_REQUEST
            ),
            $attribute
        );

        static::assertEquals(expected: $fixturePermission->name, actual: $attribute->getAttributes());
    }

    protected function getIsGranted(): IsGranted
    {
        return new IsGranted(data: FixturePermission::TEST);
    }
}
