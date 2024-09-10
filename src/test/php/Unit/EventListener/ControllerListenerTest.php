<?php

/*
 * Copyright (c) 2016 - 2024 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Unit\EventListener;

use Itspire\Common\Enum\Http\HttpResponseStatus;
use Itspire\FrameworkExtraBundle\Attribute as ItspireFrameworkExtra;
use Itspire\FrameworkExtraBundle\EventListener\ControllerListener;
use Itspire\FrameworkExtraBundle\Tests\Unit\Fixtures\FixtureController;
use Itspire\FrameworkExtraBundle\Util\Strategy\Attribute\AttributeHandlerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ControllerListenerTest extends TestCase
{
    private ?MockObject $attributeHandlerMock = null;
    private ?Request $request = null;
    private ?ControllerListener $controllerListener = null;

    protected function setUp(): void
    {
        $this->attributeHandlerMock = $this->getMockBuilder(AttributeHandlerInterface::class)->getMock();

        $file = new UploadedFile(realpath(__DIR__ . '/../../../resources/uploadedFile.txt'), 'uploadedFile.txt');
        $this->request = new Request(
            query: ['param' => 'test1'],
            request: ['pParam' => 10],
            files: ['fParam' => $file],
            content: 'body'
        );

        $this->controllerListener = new ControllerListener($this->attributeHandlerMock);
    }

    protected function tearDown(): void
    {
        unset($this->controllerListener, $this->attributeHandlerMock);
    }

    #[Test]
    public function noAttributeToProcessTest(): void
    {
        $this->request->headers->set(key: 'Accept', values: '*/*');

        $this->attributeHandlerMock->expects($this->never())->method('process');

        $this->controllerListener->onKernelController(
            new ControllerEvent(
                $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
                [new FixtureController(), 'fixture'],
                $this->request,
                HttpKernelInterface::MAIN_REQUEST
            )
        );
    }

    #[Test]
    public function oneAttributeToProcessTest(): void
    {
        $this->request->headers->set(key: 'Accept', values: '*/*');

        $attribute = new ItspireFrameworkExtra\Route(path: '/test', responseStatus: HttpResponseStatus::HTTP_OK);

        $event = new ControllerEvent(
            $this->getMockBuilder(HttpKernelInterface::class)->getMock(),
            [new FixtureController(), 'fixtureWithAttribute'],
            $this->request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->attributeHandlerMock->expects($this->once())->method('process')->with($event, $attribute);

        $this->controllerListener->onKernelController($event);
    }
}
