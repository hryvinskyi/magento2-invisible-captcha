<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Geo;

use Hryvinskyi\InvisibleCaptcha\Api\Geo\CountrySourceInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Geo\SourcePool;
use PHPUnit\Framework\TestCase;

class SourcePoolTest extends TestCase
{
    public function testEmptyPoolReturnsNothing(): void
    {
        $pool = new SourcePool();
        $this->assertSame([], $pool->getAll());
        $this->assertNull($pool->get('cloudflare'));
    }

    public function testRegistersSourcesByCode(): void
    {
        $source = $this->createMock(CountrySourceInterface::class);
        $source->method('getCode')->willReturn('cloudflare');

        $pool = new SourcePool(['ignored_key' => $source]);
        $all = $pool->getAll();

        $this->assertArrayHasKey('cloudflare', $all);
        $this->assertSame($source, $all['cloudflare']);
        $this->assertSame($source, $pool->get('cloudflare'));
    }

    public function testSkipsNonSourceInterfaceEntries(): void
    {
        $source = $this->createMock(CountrySourceInterface::class);
        $source->method('getCode')->willReturn('cloudflare');

        $pool = new SourcePool([$source, 'string', null, new \stdClass()]);

        $this->assertCount(1, $pool->getAll());
        $this->assertNotNull($pool->get('cloudflare'));
    }

    public function testGetReturnsNullForUnknown(): void
    {
        $pool = new SourcePool();
        $this->assertNull($pool->get('nope'));
    }
}
