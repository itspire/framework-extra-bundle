<?php

/*
 * Copyright (c) 2016 - 2020 Itspire.
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
            MimeType::APPLICATION_XML,
            $this->mimeTypeMatcher->findMimeTypeMatch(
                ['*/*'],
                [MimeType::APPLICATION_XML, MimeType::APPLICATION_JSON, MimeType::TEXT_HTML]
            )
        );
    }

    /** @test */
    public function findMimeTypeMatchSingleMatchTest(): void
    {
        static::assertEquals(
            MimeType::APPLICATION_JSON,
            $this->mimeTypeMatcher->findMimeTypeMatch(
                [MimeType::APPLICATION_JSON],
                [MimeType::APPLICATION_XML, MimeType::APPLICATION_JSON, MimeType::TEXT_HTML]
            )
        );
    }

    /** @test */
    public function findMimeTypeMatchLeadingWildcardTest(): void
    {
        static::assertEquals(
            MimeType::APPLICATION_JSON,
            $this->mimeTypeMatcher->findMimeTypeMatch(
                ['*/json'],
                [MimeType::APPLICATION_XML, MimeType::APPLICATION_JSON, MimeType::TEXT_HTML]
            )
        );
    }

    /** @test */
    public function findMimeTypeMatchEndingWildcardTest(): void
    {
        static::assertEquals(
            MimeType::APPLICATION_XML,
            $this->mimeTypeMatcher->findMimeTypeMatch(
                ['application/*'],
                [MimeType::TEXT_HTML, MimeType::APPLICATION_XML, MimeType::APPLICATION_JSON]
            )
        );
    }

    /** @test */
    public function findMimeTypeMatchNoMatchTest(): void
    {
        static::assertNull(
            $this->mimeTypeMatcher->findMimeTypeMatch(
                [MimeType::TEXT_HTML],
                [MimeType::APPLICATION_XML, MimeType::APPLICATION_JSON]
            )
        );
    }
}
