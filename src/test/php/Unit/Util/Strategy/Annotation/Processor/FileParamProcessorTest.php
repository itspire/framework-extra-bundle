<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Unit\Util\Strategy\Annotation\Processor;

use Itspire\FrameworkExtraBundle\Annotation\Consumes;
use Itspire\FrameworkExtraBundle\Annotation\FileParam;
use Itspire\FrameworkExtraBundle\Tests\Unit\Fixtures\FixtureController;
use Itspire\FrameworkExtraBundle\Util\Strategy\Annotation\Processor\FileParamProcessor;
use Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck\TypeCheckHandlerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class FileParamProcessorTest extends TestCase
{
    private ?FileParamProcessor $fileParamProcessor = null;

    protected function setUp(): void
    {
        parent::setUp();

        $loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $typeCheckHandlerMock = $this->getMockBuilder(TypeCheckHandlerInterface::class)->getMock();

        $this->fileParamProcessor = new FileParamProcessor($loggerMock, $typeCheckHandlerMock);
    }

    protected function tearDown(): void
    {
        unset($this->loggerMock, $this->fileParamProcessor);

        parent::tearDown();
    }

    public function supportsProvider(): array
    {
        return [
            'notSupported' => [new Consumes([]), false],
            'supported' => [new FileParam([]), true],
        ];
    }

    /**
     * @test
     * @dataProvider supportsProvider
     */
    public function supportsTest($type, $result): void
    {
        static::assertEquals($result, $this->fileParamProcessor->supports($type));
    }

    /** @test */
    public function processTest(): void
    {
        $file = new UploadedFile(
            realpath(__DIR__ . '/../../../../../../resources/uploadedFile.txt'),
            'uploadedFile.txt'
        );
        $request = new Request([], [], [], [], ['param' => $file]);

        $this->fileParamProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'param'],
                $request,
                HttpKernelInterface::MASTER_REQUEST
            ),
            new FileParam(['name' => 'param'])
        );

        static::assertInstanceOf(UploadedFile::class, $request->attributes->get('param'));
    }
}
