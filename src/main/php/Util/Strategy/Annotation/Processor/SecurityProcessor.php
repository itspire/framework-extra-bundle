<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Util\Strategy\Annotation\Processor;

use Itspire\FrameworkExtraBundle\Annotation\Security;
use Itspire\FrameworkExtraBundle\Attribute\AttributeInterface;
use Itspire\FrameworkExtraBundle\Util\Strategy\Attribute\Processor\SecurityProcessor as AttributeSecurityProcessor;

/** @deprecated */
class SecurityProcessor extends AttributeSecurityProcessor implements AnnotationProcessorInterface
{
    public function supports(AttributeInterface $annotation): bool
    {
        return $annotation instanceof Security;
    }
}
