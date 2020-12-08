<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Unit\Util\Strategy\Annotation\Processor;

use Itspire\FrameworkExtraBundle\Annotation\Consumes;
use Itspire\FrameworkExtraBundle\Annotation\Security;
use Itspire\FrameworkExtraBundle\Tests\Unit\Fixtures\FixtureController;
use Itspire\FrameworkExtraBundle\Util\Strategy\Annotation\Processor\SecurityProcessor;
use Itspire\Http\Common\Enum\HttpResponseStatus;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class SecurityProcessorTest extends TestCase
{
    private ?MockObject $loggerMock = null;
    private ?SecurityProcessor $securityProcessor = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $this->securityProcessor = new SecurityProcessor($this->loggerMock);
    }

    protected function tearDown(): void
    {
        unset($this->loggerMock, $this->securityProcessor);

        parent::tearDown();
    }

    public function supportsProvider(): array
    {
        return [
            'notSupported' => [new Consumes([]), false],
            'supported' => [new Security([]), true],
        ];
    }

    /**
     * @test
     * @dataProvider supportsProvider
     */
    public function supportsTest($type, $result): void
    {
        static::assertEquals($result, $this->securityProcessor->supports($type));
    }

    /** @test */
    public function processTest(): void
    {
        $annotation = new Security(['expression' => false, 'responseStatus' => HttpResponseStatus::HTTP_FORBIDDEN]);

        $this->securityProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'param'],
                new Request(),
                HttpKernelInterface::MASTER_REQUEST
            ),
            $annotation
        );

        static::assertEquals(HttpResponseStatus::HTTP_FORBIDDEN[0], $annotation->getStatusCode());
        static::assertEquals(HttpResponseStatus::HTTP_FORBIDDEN[1], $annotation->getMessage());
    }
}
