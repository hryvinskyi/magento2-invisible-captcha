<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Resolver;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderPoolInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Resolver\CaptchaConfig;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CaptchaConfigTest extends TestCase
{
    /** @var ConfigInterface&MockObject */
    private ConfigInterface $config;
    /** @var ProviderPoolInterface&MockObject */
    private ProviderPoolInterface $providerPool;
    /** @var Field&MockObject */
    private Field $field;
    /** @var ResolveInfo&MockObject */
    private ResolveInfo $resolveInfo;
    private CaptchaConfig $resolver;

    protected function setUp(): void
    {
        $this->config = $this->createMock(ConfigInterface::class);
        $this->providerPool = $this->createMock(ProviderPoolInterface::class);
        $this->field = $this->createMock(Field::class);
        $this->resolveInfo = $this->createMock(ResolveInfo::class);

        $this->resolver = new CaptchaConfig($this->config, $this->providerPool);
    }

    public function testReturnsDisabledWhenModuleIsOff(): void
    {
        $this->config->method('isEnabled')->willReturn(false);
        // No provider lookup should happen when the module is disabled.
        $this->providerPool->expects($this->never())->method('getActive');

        $result = $this->resolver->resolve($this->field, null, $this->resolveInfo, null, []);

        $this->assertSame(['is_enabled' => false], $result);
    }

    public function testReturnsProviderConfigWithoutFormType(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->providerPool->method('getActive')->willReturn($this->provider());

        $result = $this->resolver->resolve($this->field, null, $this->resolveInfo, null, []);

        $this->assertTrue($result['is_enabled']);
        $this->assertSame('recaptcha_v3', $result['provider']);
        $this->assertSame('site-key-123', $result['site_key']);
        $this->assertSame('g-recaptcha-response', $result['response_param']);
        $this->assertSame('https://example.test/api.js', $result['script_url']);
        $this->assertTrue($result['is_score_based']);
        // Without a form type there is neither an action nor a threshold.
        $this->assertNull($result['action']);
        $this->assertNull($result['score_threshold']);
    }

    public function testReturnsActionAndThresholdForScoreBasedProviderWithFormType(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('isFormProtectionEnabled')->willReturn(true);
        $this->config->method('isFormEnabled')->with('place_order')->willReturn(true);
        $this->config->method('getFormScoreThreshold')->with('place_order')->willReturn(0.6);
        $this->providerPool->method('getActive')->willReturn($this->provider());

        $result = $this->resolver->resolve(
            $this->field,
            null,
            $this->resolveInfo,
            null,
            ['formType' => 'place_order']
        );

        $this->assertTrue($result['is_enabled']);
        $this->assertSame('place_order', $result['action']);
        $this->assertSame(0.6, $result['score_threshold']);
    }

    public function testFormDisabledMakesIsEnabledFalseButStillReturnsProviderData(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('isFormProtectionEnabled')->willReturn(true);
        $this->config->method('isFormEnabled')->with('place_order')->willReturn(false);
        $this->config->method('getFormScoreThreshold')->with('place_order')->willReturn(0.6);
        $this->providerPool->method('getActive')->willReturn($this->provider());

        $result = $this->resolver->resolve(
            $this->field,
            null,
            $this->resolveInfo,
            null,
            ['formType' => 'place_order']
        );

        $this->assertFalse($result['is_enabled']);
        $this->assertSame('place_order', $result['action']);
        // Threshold is still derived because the provider is score-based.
        $this->assertSame(0.6, $result['score_threshold']);
    }

    public function testNonScoreBasedProviderWithFormTypeHasNullThreshold(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('isFormProtectionEnabled')->willReturn(true);
        $this->config->method('isFormEnabled')->with('place_order')->willReturn(true);
        $this->config->expects($this->never())->method('getFormScoreThreshold');
        $this->providerPool->method('getActive')->willReturn($this->provider(false));

        $result = $this->resolver->resolve(
            $this->field,
            null,
            $this->resolveInfo,
            null,
            ['formType' => 'place_order']
        );

        $this->assertSame('place_order', $result['action']);
        $this->assertNull($result['score_threshold']);
        $this->assertFalse($result['is_score_based']);
    }

    /**
     * @return ProviderInterface&MockObject
     */
    private function provider(bool $scoreBased = true): ProviderInterface
    {
        $provider = $this->createMock(ProviderInterface::class);
        $provider->method('getRenderConfig')->willReturn([]);
        $provider->method('getCode')->willReturn('recaptcha_v3');
        $provider->method('getSiteKey')->willReturn('site-key-123');
        $provider->method('getResponseParamName')->willReturn('g-recaptcha-response');
        $provider->method('getClientScriptUrl')->willReturn('https://example.test/api.js');
        $provider->method('isScoreBased')->willReturn($scoreBased);

        return $provider;
    }
}
