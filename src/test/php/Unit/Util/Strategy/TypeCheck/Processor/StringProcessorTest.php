<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Unit\Util\Strategy\TypeCheck\Processor;

use Itspire\Exception\Http\Definition\HttpExceptionDefinition;
use Itspire\Exception\Http\HttpException;
use Itspire\FrameworkExtraBundle\Annotation\QueryParam;
use Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck\Processor\StringProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class StringProcessorTest extends TestCase
{
    private ?MockObject $loggerMock = null;
    private ?StringProcessor $stringProcessor = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $this->stringProcessor = new StringProcessor($this->loggerMock);
    }

    protected function tearDown(): void
    {
        unset($this->loggerMock, $this->stringProcessor);

        parent::tearDown();
    }

    public function supportsProvider(): array
    {
        return [
            'notSupported' => ['int', false],
            'supported' => ['string', true],
        ];
    }

    /**
     * @test
     * @dataProvider supportsProvider
     */
    public function supportsTest($type, $result): void
    {
        static::assertEquals($result, $this->stringProcessor->supports($type));
    }

    /** @test */
    public function processUnsupportedTest(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(HttpExceptionDefinition::HTTP_BAD_REQUEST[0]);
        $this->expectExceptionMessage(HttpExceptionDefinition::HTTP_BAD_REQUEST[1]);

        $annotation = new QueryParam(['name' => 'param']);

        $request = new Request();
        $request->attributes->set('_route', 'test');

        $this->loggerMock
            ->expects(static::once())
            ->method('alert')
            ->with(
                sprintf(
                    'Invalid value type integer provided for parameter %s on route %s : expected one of %s.',
                    $annotation->getName(),
                    $request->attributes->get('_route'),
                    implode(', ', $this->stringProcessor->getTypes())
                )
            );

        $this->stringProcessor->process($annotation, $request, 1);
    }

    /** @test */
    public function processTest(): void
    {
        static::assertEquals(
            '111',
            $this->stringProcessor->process(new QueryParam(['name' => 'param']), new Request(), '111')
        );
    }
}
