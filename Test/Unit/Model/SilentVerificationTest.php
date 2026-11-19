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
use Hryvinskyi\InvisibleCaptcha\Api\RequestCheckerInterface;
use Hryvinskyi\InvisibleCaptcha\Api\VerificationCookieInterface;
use Hryvinskyi\InvisibleCaptcha\Model\SilentVerification;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SilentVerificationTest extends TestCase
{
    private const MODES = [
        ProviderInterface::CODE_TURNSTILE => 'widget',
        ProviderInterface::CODE_RECAPTCHA_V3 => 'score',
    ];

    /** @var ConfigInterface&MockObject */
    private ConfigInterface $config;
    /** @var RequestCheckerInterface&MockObject */
    private RequestCheckerInterface $requestChecker;
    /** @var ProviderInterface&MockObject */
    private ProviderInterface $provider;
    /** @var VerificationCookieInterface&MockObject */
    private VerificationCookieInterface $cookie;
    private SilentVerification $silentVerification;

    protected function setUp(): void
    {
        $this->config = $this->createMock(ConfigInterface::class);
        $this->requestChecker = $this->createMock(RequestCheckerInterface::class);
        $this->provider = $this->createMock(ProviderInterface::class);
        $this->cookie = $this->createMock(VerificationCookieInterface::class);

        $providerPool = $this->createMock(ProviderPoolInterface::class);
        $providerPool->method('getRouteGateProvider')->willReturn($this->provider);
        $url = $this->createMock(UrlInterface::class);
        $url->method('getBaseUrl')->willReturn('https://shop.example/');

        $this->silentVerification = new SilentVerification(
            $this->config,
            $this->requestChecker,
            $providerPool,
            $this->cookie,
            $url,
            self::MODES
        );
    }

    public function testActiveForAnUnverifiedVisitorWithASilentProvider(): void
    {
        $this->givenEnabledAndConfigured(ProviderInterface::CODE_TURNSTILE);
        $this->cookie->method('isVerified')->willReturn(false);

        $this->assertTrue($this->silentVerification->isActive());
    }

    public function testInactiveForAVerifiedVisitor(): void
    {
        $this->givenEnabledAndConfigured(ProviderInterface::CODE_TURNSTILE);
        $this->cookie->method('isVerified')->willReturn(true);

        $this->assertFalse($this->silentVerification->isActive());
    }

    public function testInactiveWhenDisabled(): void
    {
        $this->config->method('isSilentVerificationEnabled')->willReturn(false);
        $this->requestChecker->method('isConfigured')->willReturn(true);
        $this->provider->method('getCode')->willReturn(ProviderInterface::CODE_TURNSTILE);
        $this->cookie->expects($this->never())->method('isVerified');

        $this->assertFalse($this->silentVerification->isActive());
    }

    public function testInactiveWhenRouteProtectionIsNotConfigured(): void
    {
        $this->config->method('isSilentVerificationEnabled')->willReturn(true);
        $this->requestChecker->method('isConfigured')->willReturn(false);
        $this->cookie->expects($this->never())->method('isVerified');

        $this->assertFalse($this->silentVerification->isActive());
    }

    public function testInactiveForAProviderThatCannotVerifyInvisibly(): void
    {
        $this->givenEnabledAndConfigured(ProviderInterface::CODE_RECAPTCHA_V2_CHECKBOX);
        $this->cookie->method('isVerified')->willReturn(false);

        $this->assertFalse($this->silentVerification->isActive());
    }

    public function testClientConfigCarriesTheProviderAndItsMode(): void
    {
        $this->provider->method('getCode')->willReturn(ProviderInterface::CODE_RECAPTCHA_V3);
        $this->provider->method('getSiteKey')->willReturn('v3-key');
        $this->provider->method('getResponseParamName')->willReturn('g-recaptcha-response');
        $this->provider->method('getClientScriptUrl')->willReturn('https://www.google.com/recaptcha/api.js');
        $this->provider->expects($this->once())->method('getRenderConfig')
            ->with(null, ['action' => 'route_gate'])
            ->willReturn(['widgetMode' => 'score', 'action' => 'route_gate']);

        $this->assertSame(
            [
                'verifyUrl' => 'https://shop.example/invisiblecaptcha/verify',
                'mode' => 'score',
                'provider' => ProviderInterface::CODE_RECAPTCHA_V3,
                'siteKey' => 'v3-key',
                'responseParam' => 'g-recaptcha-response',
                'scriptUrl' => 'https://www.google.com/recaptcha/api.js',
                'render' => ['widgetMode' => 'score', 'action' => 'route_gate'],
            ],
            $this->silentVerification->getClientConfig()
        );
    }

    private function givenEnabledAndConfigured(string $providerCode): void
    {
        $this->config->method('isSilentVerificationEnabled')->willReturn(true);
        $this->requestChecker->method('isConfigured')->willReturn(true);
        $this->provider->method('getCode')->willReturn($providerCode);
    }
}
