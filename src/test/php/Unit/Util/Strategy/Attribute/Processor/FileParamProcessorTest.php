<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Unit\Util\Strategy\Attribute\Processor;

use Itspire\FrameworkExtraBundle\Attribute\Consumes;
use Itspire\FrameworkExtraBundle\Attribute\FileParam;
use Itspire\FrameworkExtraBundle\Tests\Unit\Fixtures\FixtureController;
use Itspire\FrameworkExtraBundle\Util\Strategy\Attribute\Processor\FileParamProcessor;
use Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck\TypeCheckHandlerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class FileParamProcessorTest extends TestCase
{
    protected ?FileParamProcessor $fileParamProcessor = null;

    protected function setUp(): void
    {
        $this->fileParamProcessor = new FileParamProcessor(
            $this->getMockBuilder(TypeCheckHandlerInterface::class)->getMock(),
            $this->getMockBuilder(LoggerInterface::class)->getMock()
        );
    }

    protected function tearDown(): void
    {
        unset($this->fileParamProcessor);
    }

    public function supportsProvider(): array
    {
        return [
            'notSupported' => [new Consumes(), false],
            'supported' => [new FileParam(name: 'param'), true],
        ];
    }

    /**
     * @test
     * @dataProvider supportsProvider
     */
    public function supportsTest($attribute, $result): void
    {
        static::assertEquals(expected: $result, actual: $this->fileParamProcessor->supports($attribute));
    }

    /** @test */
    public function processTest(): void
    {
        $file = new UploadedFile(
            realpath(__DIR__ . '/../../../../../../resources/uploadedFile.txt'),
            'uploadedFile.txt'
        );
        $request = new Request(files: ['param' => $file]);

        $this->fileParamProcessor->process(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'param'],
                $request,
                HttpKernelInterface::MAIN_REQUEST
            ),
            $this->getFileParam()
        );

        static::assertInstanceOf(expected: UploadedFile::class, actual: $request->attributes->get(key: 'param'));
    }

    protected function getFileParam(): FileParam
    {
        return new FileParam(name: 'param');
    }
}
