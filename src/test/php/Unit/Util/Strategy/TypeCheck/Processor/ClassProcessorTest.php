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
use Itspire\FrameworkExtraBundle\Annotation\BodyParam;
use Itspire\FrameworkExtraBundle\Tests\TestApp\Model\TestObject;
use Itspire\FrameworkExtraBundle\Util\Strategy\TypeCheck\Processor\ClassProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class ClassProcessorTest extends TestCase
{
    private ?MockObject $loggerMock = null;
    private ?ClassProcessor $classProcessor = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $this->classProcessor = new ClassProcessor($this->loggerMock);
    }

    protected function tearDown(): void
    {
        unset($this->loggerMock, $this->classProcessor);

        parent::tearDown();
    }

    public function supportsProvider(): array
    {
        return [
            'notSupported' => ['int', false],
            'supported' => ['class', true],
        ];
    }

    /**
     * @test
     * @dataProvider supportsProvider
     */
    public function supportsTest($type, $result): void
    {
        static::assertEquals($result, $this->classProcessor->supports($type));
    }

    /** @test */
    public function processUnsupportedTest(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(HttpExceptionDefinition::HTTP_BAD_REQUEST[0]);
        $this->expectExceptionMessage(HttpExceptionDefinition::HTTP_BAD_REQUEST[1]);

        $annotation = new BodyParam(['name' => 'param', 'type' => 'class', 'class' => TestObject::class]);

        $request = new Request();
        $request->attributes->set('_route', 'test');

        $this->loggerMock
            ->expects(static::once())
            ->method('alert')
            ->with(
                sprintf(
                    'Invalid value type integer provided for parameter %s on route %s : expected one of string.',
                    $annotation->getName(),
                    $request->attributes->get('_route')
                )
            );

        $this->classProcessor->process($annotation, $request, 1);
    }

    /** @test */
    public function processTest(): void
    {
        static::assertEquals(
            '<testObject testProperty="test" testProperty2=2></testObject>',
            $this->classProcessor->process(
                new BodyParam(['name' => 'param', 'type' => 'class', 'class' => TestObject::class]),
                new Request(),
                '<testObject testProperty="test" testProperty2=2></testObject>'
            )
        );
    }
}
