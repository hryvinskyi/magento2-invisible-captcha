<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Config\Source;

use Hryvinskyi\InvisibleCaptcha\Api\Geo\CountrySourceInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Geo\CountrySourcePoolInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Config\Source\GeoSource;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GeoSourceTest extends TestCase
{
    /** @var CountrySourcePoolInterface&MockObject */
    private CountrySourcePoolInterface $sourcePool;
    private GeoSource $source;

    protected function setUp(): void
    {
        $this->sourcePool = $this->createMock(CountrySourcePoolInterface::class);
        $this->source = new GeoSource($this->sourcePool);
    }

    public function testToOptionArrayMapsPoolSourcesInOrder(): void
    {
        $cloudflare = $this->makeSource('cloudflare', 'Cloudflare (CF-IPCountry header)');
        $maxmind = $this->makeSource('maxmind', 'MaxMind database');
        $this->sourcePool->method('getAll')->willReturn([$cloudflare, $maxmind]);

        $options = $this->source->toOptionArray();

        $this->assertCount(2, $options);
        $this->assertSame('cloudflare', $options[0]['value']);
        $this->assertSame('Cloudflare (CF-IPCountry header)', (string)$options[0]['label']);
        $this->assertSame('maxmind', $options[1]['value']);
        $this->assertSame('MaxMind database', (string)$options[1]['label']);
    }

    public function testToOptionArrayWithEmptyPool(): void
    {
        $this->sourcePool->method('getAll')->willReturn([]);

        $this->assertSame([], $this->source->toOptionArray());
    }

    /**
     * @return CountrySourceInterface&MockObject
     */
    private function makeSource(string $code, string $label): CountrySourceInterface
    {
        $source = $this->createMock(CountrySourceInterface::class);
        $source->method('getCode')->willReturn($code);
        $source->method('getLabel')->willReturn(__($label));

        return $source;
    }
}
