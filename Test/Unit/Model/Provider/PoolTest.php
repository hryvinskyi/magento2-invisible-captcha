<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Provider;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderInterface;
use Hryvinskyi\InvisibleCaptcha\Exception\ProviderNotFoundException;
use Hryvinskyi\InvisibleCaptcha\Model\Provider\Pool;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PoolTest extends TestCase
{
    /** @var ConfigInterface&MockObject */
    private ConfigInterface $config;

    protected function setUp(): void
    {
        $this->config = $this->createMock(ConfigInterface::class);
    }

    public function testGetReturnsRegisteredProvider(): void
    {
        $turnstile = $this->makeProvider(ProviderInterface::CODE_TURNSTILE);
        $pool = new Pool($this->config, [$turnstile]);

        $this->assertSame($turnstile, $pool->get(ProviderInterface::CODE_TURNSTILE));
        $this->assertTrue($pool->has(ProviderInterface::CODE_TURNSTILE));
    }

    public function testGetThrowsForUnknownCode(): void
    {
        $pool = new Pool($this->config, [$this->makeProvider(ProviderInterface::CODE_TURNSTILE)]);

        $this->expectException(ProviderNotFoundException::class);
        $pool->get('does-not-exist');
    }

    public function testConstructorRejectsNonProviderEntries(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Pool($this->config, ['not-a-provider']);
    }

    public function testGetActiveUsesConfiguredProvider(): void
    {
        $v3 = $this->makeProvider(ProviderInterface::CODE_RECAPTCHA_V3);
        $turnstile = $this->makeProvider(ProviderInterface::CODE_TURNSTILE);
        $this->config->method('getActiveProvider')->willReturn(ProviderInterface::CODE_TURNSTILE);

        $pool = new Pool($this->config, [$v3, $turnstile]);

        $this->assertSame($turnstile, $pool->getActive());
    }

    public function testGetActiveFallsBackToFirstWhenConfiguredMissing(): void
    {
        $v3 = $this->makeProvider(ProviderInterface::CODE_RECAPTCHA_V3);
        $turnstile = $this->makeProvider(ProviderInterface::CODE_TURNSTILE);
        $this->config->method('getActiveProvider')->willReturn('unknown-code');

        $pool = new Pool($this->config, [$v3, $turnstile]);

        $this->assertSame($v3, $pool->getActive());
    }

    public function testGetActiveThrowsWhenNoProvidersRegistered(): void
    {
        $this->config->method('getActiveProvider')->willReturn(ProviderInterface::CODE_TURNSTILE);
        $pool = new Pool($this->config, []);

        $this->expectException(ProviderNotFoundException::class);
        $pool->getActive();
    }

    public function testGetRouteGateProviderUsesOverride(): void
    {
        $v3 = $this->makeProvider(ProviderInterface::CODE_RECAPTCHA_V3);
        $turnstile = $this->makeProvider(ProviderInterface::CODE_TURNSTILE);
        $this->config->method('getRouteProviderOverride')->willReturn(ProviderInterface::CODE_RECAPTCHA_V3);

        $pool = new Pool($this->config, [$turnstile, $v3]);

        $this->assertSame($v3, $pool->getRouteGateProvider());
    }

    public function testGetRouteGateProviderFallsBackToActiveWhenNoOverride(): void
    {
        $v3 = $this->makeProvider(ProviderInterface::CODE_RECAPTCHA_V3);
        $turnstile = $this->makeProvider(ProviderInterface::CODE_TURNSTILE);
        $this->config->method('getRouteProviderOverride')->willReturn('');
        $this->config->method('getActiveProvider')->willReturn(ProviderInterface::CODE_TURNSTILE);

        $pool = new Pool($this->config, [$v3, $turnstile]);

        $this->assertSame($turnstile, $pool->getRouteGateProvider());
    }

    public function testGetRouteGateProviderFallsBackToActiveWhenOverrideUnknown(): void
    {
        $v3 = $this->makeProvider(ProviderInterface::CODE_RECAPTCHA_V3);
        $turnstile = $this->makeProvider(ProviderInterface::CODE_TURNSTILE);
        $this->config->method('getRouteProviderOverride')->willReturn('unknown-code');
        $this->config->method('getActiveProvider')->willReturn(ProviderInterface::CODE_TURNSTILE);

        $pool = new Pool($this->config, [$v3, $turnstile]);

        $this->assertSame($turnstile, $pool->getRouteGateProvider());
    }

    public function testGetFallbackProviderReturnsNullWhenDisabled(): void
    {
        $this->config->method('isRouteFallbackEnabled')->willReturn(false);
        $pool = new Pool($this->config, [$this->makeProvider(ProviderInterface::CODE_TURNSTILE)]);

        $this->assertNull($pool->getFallbackProvider());
    }

    public function testGetFallbackProviderReturnsNullWhenCodeEmpty(): void
    {
        $this->config->method('isRouteFallbackEnabled')->willReturn(true);
        $this->config->method('getRouteFallbackProvider')->willReturn('');
        $pool = new Pool($this->config, [$this->makeProvider(ProviderInterface::CODE_TURNSTILE)]);

        $this->assertNull($pool->getFallbackProvider());
    }

    public function testGetFallbackProviderReturnsNullWhenProviderNotConfigured(): void
    {
        $this->config->method('isRouteFallbackEnabled')->willReturn(true);
        $this->config->method('getRouteFallbackProvider')->willReturn(ProviderInterface::CODE_TURNSTILE);

        $turnstile = $this->makeProvider(ProviderInterface::CODE_TURNSTILE);
        $turnstile->method('isConfigured')->willReturn(false);

        $pool = new Pool($this->config, [$turnstile]);

        $this->assertNull($pool->getFallbackProvider());
    }

    public function testGetFallbackProviderReturnsConfiguredProvider(): void
    {
        $this->config->method('isRouteFallbackEnabled')->willReturn(true);
        $this->config->method('getRouteFallbackProvider')->willReturn(ProviderInterface::CODE_TURNSTILE);

        $turnstile = $this->makeProvider(ProviderInterface::CODE_TURNSTILE);
        $turnstile->method('isConfigured')->willReturn(true);

        $pool = new Pool($this->config, [$turnstile]);

        $this->assertSame($turnstile, $pool->getFallbackProvider());
    }

    /**
     * @return ProviderInterface&MockObject
     */
    private function makeProvider(string $code): ProviderInterface
    {
        $provider = $this->createMock(ProviderInterface::class);
        $provider->method('getCode')->willReturn($code);

        return $provider;
    }
}
