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
use Hryvinskyi\InvisibleCaptcha\Model\Provider\RecaptchaV2Checkbox;
use Hryvinskyi\InvisibleCaptcha\Model\Verification\VerificationRequest;
use Hryvinskyi\InvisibleCaptcha\Model\Verification\VerificationRequestFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RecaptchaV2CheckboxTest extends TestCase
{
    private const CODE = ProviderInterface::CODE_RECAPTCHA_V2_CHECKBOX;
    private const DEFAULT_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    /** @var ProviderConfigInterface&MockObject */
    private ProviderConfigInterface $providerConfig;
    /** @var VerifierInterface&MockObject */
    private VerifierInterface $verifier;
    /** @var VerificationRequestFactory&MockObject */
    private VerificationRequestFactory $requestFactory;
    private RecaptchaV2Checkbox $provider;

    protected function setUp(): void
    {
        $this->providerConfig = $this->createMock(ProviderConfigInterface::class);
        $this->verifier = $this->createMock(VerifierInterface::class);
        $this->requestFactory = $this->createMock(VerificationRequestFactory::class);
        $this->requestFactory->method('create')
            ->willReturnCallback(static fn (): VerificationRequest => new VerificationRequest());

        $this->provider = new RecaptchaV2Checkbox($this->providerConfig, $this->verifier, $this->requestFactory);
    }

    public function testMetadata(): void
    {
        $this->assertSame(self::CODE, $this->provider->getCode());
        $this->assertFalse($this->provider->isScoreBased());
        $this->assertFalse($this->provider->supportsAction());
        $this->assertSame('g-recaptcha-response', $this->provider->getResponseParamName());
        $this->assertSame('https://www.google.com/recaptcha/api.js', $this->provider->getClientScriptUrl());
        $this->assertSame(110000, $this->provider->getTokenTtlMs());
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

    public function testGetRenderConfigUsesConfiguredThemeAndSize(): void
    {
        $this->providerConfig->method('getSiteKey')->willReturn('SITE');
        $this->providerConfig->method('getWidgetOption')->willReturnMap([
            [self::CODE, 'theme', null, 'dark'],
            [self::CODE, 'size', null, 'compact'],
        ]);

        $config = $this->provider->getRenderConfig();

        $this->assertSame(self::CODE, $config['provider']);
        $this->assertFalse($config['isScoreBased']);
        $this->assertFalse($config['supportsAction']);
        $this->assertSame('dark', $config['theme']);
        $this->assertSame('compact', $config['size']);
        $this->assertSame('explicit', $config['widgetMode']);
    }

    public function testGetRenderConfigFallsBackToDefaultsAndOmitsAction(): void
    {
        $this->providerConfig->method('getSiteKey')->willReturn('SITE');
        $this->providerConfig->method('getWidgetOption')->willReturn(null);

        $config = $this->provider->getRenderConfig(null, ['action' => 'login']);

        $this->assertSame('light', $config['theme']);
        $this->assertSame('normal', $config['size']);
        $this->assertArrayNotHasKey('action', $config);
    }

    public function testCreateVerificationRequestSetsSecretAndVerifyUrl(): void
    {
        $this->providerConfig->method('getSecretKey')->with(self::CODE, null)->willReturn('SECRET');
        $this->providerConfig->method('getWidgetOption')->with(self::CODE, 'verify_url', null)->willReturn(null);

        $request = $this->provider->createVerificationRequest();

        $this->assertInstanceOf(VerificationRequest::class, $request);
        $this->assertSame('SECRET', $request->getSecret());
        $this->assertSame(self::DEFAULT_VERIFY_URL, $request->getVerifyUrl());
    }
}
