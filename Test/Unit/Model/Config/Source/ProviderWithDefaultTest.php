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
use Hryvinskyi\InvisibleCaptcha\Model\Config\Source\ProviderWithDefault;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProviderWithDefaultTest extends TestCase
{
    /** @var ProviderPoolInterface&MockObject */
    private ProviderPoolInterface $providerPool;

    protected function setUp(): void
    {
        $this->providerPool = $this->createMock(ProviderPoolInterface::class);
    }

    public function testToOptionArrayPrependsEmptyOption(): void
    {
        $turnstile = $this->makeProvider(ProviderInterface::CODE_TURNSTILE, 'Cloudflare Turnstile');
        $this->providerPool->method('getAll')->willReturn([$turnstile]);

        $source = new ProviderWithDefault($this->providerPool);
        $options = $source->toOptionArray();

        $this->assertCount(2, $options);
        $this->assertSame('', $options[0]['value']);
        $this->assertSame('Use active provider', (string)$options[0]['label']);
        $this->assertSame(ProviderInterface::CODE_TURNSTILE, $options[1]['value']);
        $this->assertSame('Cloudflare Turnstile', (string)$options[1]['label']);
    }

    public function testToOptionArrayHonoursCustomEmptyLabel(): void
    {
        $this->providerPool->method('getAll')->willReturn([]);

        $source = new ProviderWithDefault($this->providerPool, 'No override');
        $options = $source->toOptionArray();

        $this->assertCount(1, $options);
        $this->assertSame('', $options[0]['value']);
        $this->assertSame('No override', (string)$options[0]['label']);
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
