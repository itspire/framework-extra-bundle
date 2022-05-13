<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Unit\Util\Strategy\TypeCheck\Processor;

use Itspire\Exception\Definition\Http\HttpExceptionDefinition;
use Itspire\Exception\Http\HttpException;
use Itspire\FrameworkExtraBundle\Annotation\BodyParam as BodyParamAnnotation;
use Itspire\FrameworkExtraBundle\Attribute\BodyParam as BodyParamAttribute;
use Itspire\FrameworkExtraBundle\Attribute\ParamAttributeInterface;
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
    public function supportsTest(string $type, bool $result): void
    {
        static::assertEquals(expected: $result, actual: $this->classProcessor->supports($type));
    }

    public function annotationOrAttributeProvider(): array
    {
        return [
            'paramAnnotation' => [
                new BodyParamAnnotation(name: 'param', type: 'class', class: TestObject::class),
            ],
            'paramAttribute' => [new BodyParamAttribute(name: 'param', type: 'class', class: TestObject::class)],
        ];
    }

    /**
     * @test
     * @dataProvider annotationOrAttributeProvider
     */
    public function processUnsupportedTest(ParamAttributeInterface $paramAttribute): void
    {
        $exceptionDefinition = HttpExceptionDefinition::HTTP_BAD_REQUEST;

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($exceptionDefinition->value);
        $this->expectExceptionMessage($exceptionDefinition->getDescription());

        $request = new Request(attributes: ['_route' => 'test']);

        $this->loggerMock
            ->expects(static::once())
            ->method('alert')
            ->with(
                sprintf(
                    'Invalid value type integer provided for parameter param on route test : expected one of %s.',
                    TestObject::class
                )
            );

        $this->classProcessor->process($paramAttribute, $request, 1);
    }

    /**
     * @test
     * @dataProvider annotationOrAttributeProvider
     */
    public function processTest(ParamAttributeInterface $paramAttribute): void
    {
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
