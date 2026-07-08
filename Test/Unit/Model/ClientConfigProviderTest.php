<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderPoolInterface;
use Hryvinskyi\InvisibleCaptcha\Model\ClientConfigProvider;
use Hryvinskyi\InvisibleCaptcha\Model\Strategy\Token\RequestParam;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ClientConfigProviderTest extends TestCase
{
    /** @var ConfigInterface&MockObject */
    private ConfigInterface $config;
    /** @var ProviderPoolInterface&MockObject */
    private ProviderPoolInterface $providerPool;
    private ClientConfigProvider $clientConfigProvider;

    protected function setUp(): void
    {
        $this->config = $this->createMock(ConfigInterface::class);
        $this->providerPool = $this->createMock(ProviderPoolInterface::class);
        $this->clientConfigProvider = new ClientConfigProvider($this->config, $this->providerPool);
    }

    public function testReturnsEmptyArrayWhenDisabled(): void
    {
        $this->config->method('isEnabled')->willReturn(false);
        $this->providerPool->expects($this->never())->method('getActive');

        $this->assertSame([], $this->clientConfigProvider->getFormConfig());
    }

    public function testMergesProviderRenderConfigWithGlobalFlags(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('isLazyLoad')->willReturn(true);
        $this->config->method('isDisableSubmitForm')->willReturn(false);

        $provider = $this->createMock(ProviderInterface::class);
        $provider->method('getRenderConfig')->willReturn([
            'provider' => ProviderInterface::CODE_RECAPTCHA_V3,
            'siteKey' => 'site-key',
            'scriptUrl' => 'https://example.test/api.js',
        ]);
        $this->providerPool->method('getActive')->willReturn($provider);

        $result = $this->clientConfigProvider->getFormConfig();

        $this->assertSame(
            [
                'provider' => ProviderInterface::CODE_RECAPTCHA_V3,
                'siteKey' => 'site-key',
                'scriptUrl' => 'https://example.test/api.js',
                'lazyLoad' => true,
                'isDisabledSubmitForm' => false,
                'tokenField' => RequestParam::DEFAULT_FIELD,
            ],
            $result
        );
    }

    public function testPassesScopeCodeAndContextToProvider(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('isLazyLoad')->willReturn(false);
        $this->config->method('isDisableSubmitForm')->willReturn(true);

        $context = ['action' => 'customer_login'];

        $provider = $this->createMock(ProviderInterface::class);
        $provider->expects($this->once())
            ->method('getRenderConfig')
            ->with('store_fr', $context)
            ->willReturn(['provider' => ProviderInterface::CODE_TURNSTILE]);

        $this->providerPool->expects($this->once())
            ->method('getActive')
            ->with('store_fr')
            ->willReturn($provider);

        $result = $this->clientConfigProvider->getFormConfig('store_fr', $context);

        $this->assertSame(ProviderInterface::CODE_TURNSTILE, $result['provider']);
        $this->assertFalse($result['lazyLoad']);
        $this->assertTrue($result['isDisabledSubmitForm']);
        $this->assertSame(RequestParam::DEFAULT_FIELD, $result['tokenField']);
    }

    public function testProviderRenderConfigKeysTakePrecedenceOnCollision(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('isLazyLoad')->willReturn(true);
        $this->config->method('isDisableSubmitForm')->willReturn(true);

        // Provider explicitly sets tokenField; the union operator must keep it.
        $provider = $this->createMock(ProviderInterface::class);
        $provider->method('getRenderConfig')->willReturn([
            'tokenField' => 'custom-field',
            'lazyLoad' => false,
        ]);
        $this->providerPool->method('getActive')->willReturn($provider);

        $result = $this->clientConfigProvider->getFormConfig();

        $this->assertSame('custom-field', $result['tokenField']);
        $this->assertFalse($result['lazyLoad']);
        $this->assertTrue($result['isDisabledSubmitForm']);
    }
}
