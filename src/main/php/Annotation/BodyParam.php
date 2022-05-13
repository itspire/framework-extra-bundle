<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Annotation;

use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Itspire\FrameworkExtraBundle\Attribute\BodyParam as AttributeBodyParam;

/**
 * @deprecated
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("METHOD")
 */
class BodyParam extends AttributeBodyParam implements AnnotationInterface
{
}
