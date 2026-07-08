<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Provider;

use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Verification\VerifierInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Provider\RecaptchaV3;
use Hryvinskyi\InvisibleCaptcha\Model\Verification\VerificationRequest;
use Hryvinskyi\InvisibleCaptcha\Model\Verification\VerificationRequestFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RecaptchaV3Test extends TestCase
{
    private const CODE = ProviderInterface::CODE_RECAPTCHA_V3;
    private const DEFAULT_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    /** @var ProviderConfigInterface&MockObject */
    private ProviderConfigInterface $providerConfig;
    /** @var VerifierInterface&MockObject */
    private VerifierInterface $verifier;
    /** @var VerificationRequestFactory&MockObject */
    private VerificationRequestFactory $requestFactory;
    private RecaptchaV3 $provider;

    protected function setUp(): void
    {
        $this->providerConfig = $this->createMock(ProviderConfigInterface::class);
        $this->verifier = $this->createMock(VerifierInterface::class);
        $this->requestFactory = $this->createMock(VerificationRequestFactory::class);
        $this->requestFactory->method('create')
            ->willReturnCallback(static fn (): VerificationRequest => new VerificationRequest());

        $this->provider = new RecaptchaV3($this->providerConfig, $this->verifier, $this->requestFactory);
    }

    public function testMetadata(): void
    {
        $this->assertSame(self::CODE, $this->provider->getCode());
        $this->assertTrue($this->provider->isScoreBased());
        $this->assertTrue($this->provider->supportsAction());
        $this->assertSame('g-recaptcha-response', $this->provider->getResponseParamName());
        $this->assertSame('https://www.google.com/recaptcha/api.js', $this->provider->getClientScriptUrl());
        $this->assertSame(90000, $this->provider->getTokenTtlMs());
        $this->assertNotSame('', (string)$this->provider->getLabel());
    }

    public function testGetVerifierReturnsInjectedVerifier(): void
    {
        $this->assertSame($this->verifier, $this->provider->getVerifier());
    }

    public function testIsConfiguredTrueWhenSiteAndSecretPresent(): void
    {
        $this->providerConfig->method('getSiteKey')->with(self::CODE, null)->willReturn('SITE');
        $this->providerConfig->method('getSecretKey')->with(self::CODE, null)->willReturn('SECRET');

        $this->assertTrue($this->provider->isConfigured());
    }

    public function testIsConfiguredFalseWhenSiteMissing(): void
    {
        $this->providerConfig->method('getSiteKey')->willReturn('');

        $this->assertFalse($this->provider->isConfigured());
    }

    public function testIsConfiguredFalseWhenSecretMissing(): void
    {
        $this->providerConfig->method('getSiteKey')->willReturn('SITE');
        $this->providerConfig->method('getSecretKey')->willReturn('');

        $this->assertFalse($this->provider->isConfigured());
    }

    public function testGetVerifyUrlReturnsDefaultWhenNoOverride(): void
    {
        $this->providerConfig->method('getWidgetOption')->with(self::CODE, 'verify_url', null)->willReturn(null);

        $this->assertSame(self::DEFAULT_VERIFY_URL, $this->provider->getVerifyUrl());
    }

    public function testGetVerifyUrlReturnsOverride(): void
    {
        $this->providerConfig->method('getWidgetOption')
            ->with(self::CODE, 'verify_url', null)
            ->willReturn('https://proxy.example/verify');

        $this->assertSame('https://proxy.example/verify', $this->provider->getVerifyUrl());
    }

    public function testGetRenderConfigContainsExpectedKeys(): void
    {
        $this->providerConfig->method('getSiteKey')->willReturn('SITE');
        $this->providerConfig->method('getWidgetOption')->willReturnMap([
            [self::CODE, 'hide_badge', null, '1'],
            [self::CODE, 'hide_badge_text', null, 'Protected by reCAPTCHA'],
        ]);

        $config = $this->provider->getRenderConfig(null, ['action' => 'login']);

        $this->assertSame(self::CODE, $config['provider']);
        $this->assertSame('SITE', $config['siteKey']);
        $this->assertSame('https://www.google.com/recaptcha/api.js', $config['scriptUrl']);
        $this->assertSame('g-recaptcha-response', $config['responseParam']);
        $this->assertSame(90000, $config['tokenTtl']);
        $this->assertTrue($config['isScoreBased']);
        $this->assertTrue($config['supportsAction']);
        $this->assertSame('login', $config['action']);
        $this->assertSame('bottomright', $config['badge']);
        $this->assertTrue($config['hideBadge']);
        $this->assertSame('Protected by reCAPTCHA', $config['hideBadgeText']);
        $this->assertSame('score', $config['widgetMode']);
    }

    public function testCreateVerificationRequestSetsSecretAndVerifyUrl(): void
    {
        $this->providerConfig->method('getSecretKey')->with(self::CODE, null)->willReturn('SECRET');
        $this->providerConfig->method('getWidgetOption')->with(self::CODE, 'verify_url', null)->willReturn(null);

        $request = $this->provider->createVerificationRequest();

        $this->assertInstanceOf(VerificationRequest::class, $request);
        $this->assertSame('SECRET', $request->getSecret());
        $this->assertSame(self::DEFAULT_VERIFY_URL, $request->getVerifyUrl());
        $this->assertSame([], $request->getExtra());
    }
}
