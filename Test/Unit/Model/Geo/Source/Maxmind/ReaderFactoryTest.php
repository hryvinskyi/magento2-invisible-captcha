<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Geo\Source\Maxmind;

use Hryvinskyi\InvisibleCaptcha\Model\Geo\Source\Maxmind\ReaderFactory;
use MaxMind\Db\Reader;
use PHPUnit\Framework\TestCase;

class ReaderFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists(Reader::class)) {
            $this->markTestSkipped('maxmind-db/reader not installed');
        }
    }

    public function testCreateWithNonexistentPathThrows(): void
    {
        $factory = new ReaderFactory();

        $this->expectException(\InvalidArgumentException::class);

        $factory->create('/nonexistent/path/does-not-exist.mmdb');
    }
}
