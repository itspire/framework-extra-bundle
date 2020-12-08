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
use Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck\Processor\BooleanProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class BooleanProcessorTest extends TestCase
{
    private ?MockObject $loggerMock = null;
    private ?BooleanProcessor $booleanProcessor = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $this->booleanProcessor = new BooleanProcessor($this->loggerMock);
    }

    protected function tearDown(): void
    {
        unset($this->loggerMock, $this->booleanProcessor);

        parent::tearDown();
    }

    public function supportsProvider(): array
    {
        return [
            'notSupported' => ['string', false],
            'boolSupport' => ['bool', true],
            'booleanSupport' => ['boolean', true],
        ];
    }

    /**
     * @test
     * @dataProvider supportsProvider
     */
    public function supportsTest($type, $result): void
    {
        static::assertEquals($result, $this->booleanProcessor->supports($type));
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
                    'Invalid value type string provided for parameter %s on route %s : expected one of %s.',
                    $annotation->getName(),
                    $request->attributes->get('_route'),
                    implode(', ', $this->booleanProcessor->getTypes())
                )
            );

        $this->booleanProcessor->process($annotation, $request, 'test');
    }

    public function processProvider(): array
    {
        return [
            'convertedTruthyInt' => [1, true],
            'convertedTruthyString' => ['true', true],
            'convertedTruthyIntegerString' => ['1', true],
            'convertedFalsyInt' => [0, false],
            'convertedFalsyString' => ['false', false],
            'convertedFalsyIntegerString' => ['0', false],
            'rawTruthy' => [true, true],
            'rawFalsy' => [false, false],
        ];
    }

    /**
     * @test
     * @dataProvider processProvider
     */
    public function processTest($initial, $result): void
    {
        static::assertEquals(
            $result,
            $this->booleanProcessor->process(new QueryParam(['name' => 'param']), new Request(), $initial)
        );
    }
}
