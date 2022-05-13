<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Unit\Util\Strategy\Annotation\Processor;

use Itspire\FrameworkExtraBundle\Annotation\Consumes;
use Itspire\FrameworkExtraBundle\Annotation\FileParam;
use Itspire\FrameworkExtraBundle\Attribute\ParamAttributeInterface;
use Itspire\FrameworkExtraBundle\Tests\Unit\Util\Strategy\Attribute\Processor as AttributeProcessorTest;
use Itspire\FrameworkExtraBundle\Util\Strategy\Annotation\Processor\FileParamProcessor;
use Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck\TypeCheckHandlerInterface;
use Psr\Log\LoggerInterface;

/** @deprecated */
class FileParamProcessorTest extends AttributeProcessorTest\FileParamProcessorTest
{
    protected function setUp(): void
    {
        $this->fileParamProcessor = new FileParamProcessor(
            $this->getMockBuilder(TypeCheckHandlerInterface::class)->getMock(),
            $this->getMockBuilder(LoggerInterface::class)->getMock()
        );
    }

    public function supportsProvider(): array
    {
        return [
            'notSupported' => [new Consumes([]), false],
            'supported' => [new FileParam(name: 'param'), true],
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

    protected function getFileParam(): ParamAttributeInterface
    {
        return new FileParam(name: 'param');
    }
}
