<?php

/*
 * Copyright (c) 2016 - 2024 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Unit\Util\Strategy\TypeCheck\Processor;

use Itspire\Exception\Definition\Http\HttpExceptionDefinition;
use Itspire\Exception\Http\HttpException;
use Itspire\FrameworkExtraBundle\Attribute\BodyParam;
use Itspire\FrameworkExtraBundle\Tests\TestApp\Model\TestObject;
use Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck\Processor\ClassProcessor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class ClassProcessorTest extends TestCase
{
    private MockObject | LoggerInterface | null $loggerMock = null;
    private ?ClassProcessor $classProcessor = null;

    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $this->classProcessor = new ClassProcessor($this->loggerMock);
    }

    protected function tearDown(): void
    {
        unset($this->classProcessor, $this->loggerMock);
    }

    public static function supportsProvider(): array
    {
        return [
            'notSupported' => ['int', false],
            'supported' => ['class', true],
        ];
    }

    #[Test]
    #[DataProvider('supportsProvider')]
    public function supportsTest(string $type, bool $result): void
    {
        static::assertEquals(expected: $result, actual: $this->classProcessor->supports($type));
    }

    #[Test]
    public function processUnsupportedTest(): void
    {
        $exceptionDefinition = HttpExceptionDefinition::HTTP_BAD_REQUEST;

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($exceptionDefinition->value);
        $this->expectExceptionMessage($exceptionDefinition->getDescription());

        $paramAttribute = new BodyParam(name: 'param', type: 'class', class: TestObject::class);
        $request = new Request(attributes: ['_route' => 'test']);

        $this->loggerMock
            ->expects($this->once())
            ->method('alert')
            ->with(
                sprintf(
                    'Invalid value type integer provided for parameter param on route test : expected one of %s.',
                    TestObject::class
                )
            );

        $this->classProcessor->process($paramAttribute, $request, 1);
    }

    #[Test]
    public function processTest(): void
    {
        $paramAttribute = new BodyParam(name: 'param', type: 'class', class: TestObject::class);

        static::assertEquals(
            '<testObject testProperty="test" testProperty2=2></testObject>',
            $this->classProcessor->process(
                $paramAttribute,
                new Request(),
                '<testObject testProperty="test" testProperty2=2></testObject>'
            )
        );
    }
}
