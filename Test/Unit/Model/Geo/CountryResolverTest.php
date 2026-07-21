<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Geo;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Geo\CountrySourceInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Geo\CountrySourcePoolInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Http\ClientIpResolverInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Geo\CountryResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CountryResolverTest extends TestCase
{
    /** @var ConfigInterface&MockObject */
    private ConfigInterface $config;
    /** @var CountrySourcePoolInterface&MockObject */
    private CountrySourcePoolInterface $sourcePool;
    /** @var ClientIpResolverInterface&MockObject */
    private ClientIpResolverInterface $clientIpResolver;
    private CountryResolver $resolver;

    protected function setUp(): void
    {
        $this->config = $this->createMock(ConfigInterface::class);
        $this->sourcePool = $this->createMock(CountrySourcePoolInterface::class);
        $this->clientIpResolver = $this->createMock(ClientIpResolverInterface::class);
        $this->resolver = new CountryResolver($this->config, $this->sourcePool, $this->clientIpResolver);
    }

    public function testResolvesViaSelectedSourcePassingClientIp(): void
    {
        $source = $this->createMock(CountrySourceInterface::class);
        $source->method('isConfigured')->willReturn(true);
        $source->expects($this->once())->method('resolve')->with('203.0.113.7')->willReturn('UA');

        $this->config->method('getGeoSource')->willReturn('cloudflare');
        $this->sourcePool->method('get')->with('cloudflare')->willReturn($source);
        $this->clientIpResolver->method('resolve')->willReturn('203.0.113.7');

        $this->assertSame('UA', $this->resolver->getCountryCode());
    }

    public function testUnknownSourceCodeReturnsNull(): void
    {
        $this->config->method('getGeoSource')->willReturn('does_not_exist');
        $this->sourcePool->method('get')->with('does_not_exist')->willReturn(null);
        $this->clientIpResolver->expects($this->never())->method('resolve');

        $this->assertNull($this->resolver->getCountryCode());
    }

    public function testUnconfiguredSourceReturnsNullWithoutResolving(): void
    {
        $source = $this->createMock(CountrySourceInterface::class);
        $source->method('isConfigured')->willReturn(false);
        $source->expects($this->never())->method('resolve');

        $this->config->method('getGeoSource')->willReturn('maxmind');
        $this->sourcePool->method('get')->willReturn($source);
        $this->clientIpResolver->expects($this->never())->method('resolve');

        $this->assertNull($this->resolver->getCountryCode());
    }

    public function testResultIsMemoizedAcrossCalls(): void
    {
        $source = $this->createMock(CountrySourceInterface::class);
        $source->method('isConfigured')->willReturn(true);
        $source->expects($this->once())->method('resolve')->willReturn('DE');

        $this->config->method('getGeoSource')->willReturn('cloudflare');
        $this->sourcePool->method('get')->willReturn($source);
        $this->clientIpResolver->method('resolve')->willReturn('203.0.113.7');

        $this->assertSame('DE', $this->resolver->getCountryCode());
        $this->assertSame('DE', $this->resolver->getCountryCode());
    }

    public function testNullResultIsAlsoMemoized(): void
    {
        $source = $this->createMock(CountrySourceInterface::class);
        $source->method('isConfigured')->willReturn(true);
        $source->expects($this->once())->method('resolve')->willReturn(null);

        $this->config->method('getGeoSource')->willReturn('cloudflare');
        $this->sourcePool->method('get')->willReturn($source);
        $this->clientIpResolver->method('resolve')->willReturn('203.0.113.7');

        $this->assertNull($this->resolver->getCountryCode());
        $this->assertNull($this->resolver->getCountryCode());
    }

    public function testResetStateClearsMemo(): void
    {
        $source = $this->createMock(CountrySourceInterface::class);
        $source->method('isConfigured')->willReturn(true);
        $source->expects($this->exactly(2))->method('resolve')->willReturn('UA');

        $this->config->method('getGeoSource')->willReturn('cloudflare');
        $this->sourcePool->method('get')->willReturn($source);
        $this->clientIpResolver->method('resolve')->willReturn('203.0.113.7');

        $this->assertSame('UA', $this->resolver->getCountryCode());
        $this->resolver->_resetState();
        $this->assertSame('UA', $this->resolver->getCountryCode());
    }
}
