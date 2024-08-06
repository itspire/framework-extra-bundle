<?php

/*
 * Copyright (c) 2016 - 2024 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\TestApp\Controller;

use Itspire\Common\Enum\Http\HttpMethod;
use Itspire\Common\Enum\Http\HttpResponseStatus;
use Itspire\Common\Enum\MimeType;
use Itspire\Exception\Definition\Http\HttpExceptionDefinition;
use Itspire\Exception\Definition\Webservice\WebserviceExceptionDefinition;
use Itspire\Exception\Http\HttpException;
use Itspire\Exception\Webservice\WebserviceException;
use Itspire\FrameworkExtraBundle\Attribute as ItspireFrameworkExtra;
use Itspire\FrameworkExtraBundle\Tests\TestApp\Enum\Role;
use Itspire\FrameworkExtraBundle\Tests\TestApp\Model\TestObject;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[ItspireFrameworkExtra\Route(path: '/security/failure', methods: HttpMethod::GET)]
#[ItspireFrameworkExtra\Security(expression: 'false', responseStatus: HttpResponseStatus::HTTP_FORBIDDEN)]
class SecurityFailTestController extends AbstractController
{
    #[ItspireFrameworkExtra\Route(path: '', name: 'securityFailTest')]
    public function securityFailTest(): Response
    {
        return new Response('fail', HttpResponseStatus::HTTP_OK->value);
    }
}
