<?php

/*
 * Copyright (c) 2016 - 2022 Itspire.
 * This software is licensed under the BSD-3-Clause license. (see LICENSE.md for full license)
 * All Right Reserved.
 */

declare(strict_types=1);

namespace Itspire\FrameworkExtraBundle\Tests\Unit\Util;

use Itspire\Common\Enum\MimeType;
use Itspire\FrameworkExtraBundle\Util\MimeTypeMatcher;
use Itspire\FrameworkExtraBundle\Util\MimeTypeMatcherInterface;
use PHPUnit\Framework\TestCase;

class MimeTypeMatcherTest extends TestCase
{
    private ?MimeTypeMatcherInterface $mimeTypeMatcher = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mimeTypeMatcher = new MimeTypeMatcher();
    }

    protected function tearDown(): void
    {
        unset($this->mimeTypeMatcher);

        parent::tearDown();
    }

    /** @test */
    public function findMimeTypeMatchWildcardTest(): void
    {
        static::assertEquals(
            expected: MimeType::APPLICATION_XML->value,
            actual: $this->mimeTypeMatcher->findMimeTypeMatch(
                requestValues: ['*/*'],
                attributeValues: [
                    MimeType::APPLICATION_XML->value,
                    MimeType::APPLICATION_JSON->value,
                    MimeType::TEXT_HTML->value,
                ]
            )
        );
    }

    /** @test */
    public function findMimeTypeMatchSingleMatchTest(): void
    {
        static::assertEquals(
            expected: MimeType::APPLICATION_JSON->value,
            actual: $this->mimeTypeMatcher->findMimeTypeMatch(
                requestValues: [MimeType::APPLICATION_JSON->value],
                attributeValues: [
                    MimeType::APPLICATION_XML->value,
                    MimeType::APPLICATION_JSON->value,
                    MimeType::TEXT_HTML->value,
                ]
            )
        );
    }

    /** @test */
    public function findMimeTypeMatchLeadingWildcardTest(): void
    {
        static::assertEquals(
            expected: MimeType::APPLICATION_JSON->value,
            actual: $this->mimeTypeMatcher->findMimeTypeMatch(
                requestValues:  ['*/json'],
                attributeValues: [
                    MimeType::APPLICATION_XML->value,
                    MimeType::APPLICATION_JSON->value,
                    MimeType::TEXT_HTML->value,
                ]
            )
        );
    }

    /** @test */
    public function findMimeTypeMatchEndingWildcardTest(): void
    {
        static::assertEquals(
            expected: MimeType::APPLICATION_XML->value,
            actual: $this->mimeTypeMatcher->findMimeTypeMatch(
                requestValues: ['application/*'],
                attributeValues: [
                    MimeType::TEXT_HTML->value,
                    MimeType::APPLICATION_XML->value,
                    MimeType::APPLICATION_JSON->value,
                ]
            )
        );
    }

    /** @test */
    public function findMimeTypeMatchNoMatchTest(): void
    {
        static::assertNull(
            actual: $this->mimeTypeMatcher->findMimeTypeMatch(
                requestValues: [MimeType::TEXT_HTML->value],
                attributeValues: [MimeType::APPLICATION_XML->value, MimeType::APPLICATION_JSON->value]
            )
        );
    }
}
