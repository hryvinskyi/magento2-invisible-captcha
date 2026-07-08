<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Config\Source;

use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderPoolInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Config\Source\Provider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProviderTest extends TestCase
{
    /** @var ProviderPoolInterface&MockObject */
    private ProviderPoolInterface $providerPool;
    private Provider $source;

    protected function setUp(): void
    {
        $this->providerPool = $this->createMock(ProviderPoolInterface::class);
        $this->source = new Provider($this->providerPool);
    }

    public function testToOptionArrayMapsPoolProviders(): void
    {
        $turnstile = $this->makeProvider(ProviderInterface::CODE_TURNSTILE, 'Cloudflare Turnstile');
        $v3 = $this->makeProvider(ProviderInterface::CODE_RECAPTCHA_V3, 'reCAPTCHA v3');
        $this->providerPool->method('getAll')->willReturn([$turnstile, $v3]);

        $options = $this->source->toOptionArray();

        $this->assertCount(2, $options);
        $this->assertSame(ProviderInterface::CODE_TURNSTILE, $options[0]['value']);
        $this->assertSame('Cloudflare Turnstile', (string)$options[0]['label']);
        $this->assertSame(ProviderInterface::CODE_RECAPTCHA_V3, $options[1]['value']);
        $this->assertSame('reCAPTCHA v3', (string)$options[1]['label']);
    }

    public function testToOptionArrayWithEmptyPool(): void
    {
        $this->providerPool->method('getAll')->willReturn([]);

        $this->assertSame([], $this->source->toOptionArray());
    }

    /**
     * @return ProviderInterface&MockObject
     */
    private function makeProvider(string $code, string $label): ProviderInterface
    {
        $provider = $this->createMock(ProviderInterface::class);
        $provider->method('getCode')->willReturn($code);
        $provider->method('getLabel')->willReturn(__($label));

        return $provider;
    }
}
