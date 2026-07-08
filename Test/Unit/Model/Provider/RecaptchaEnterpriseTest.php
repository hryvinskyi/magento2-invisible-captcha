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
use Hryvinskyi\InvisibleCaptcha\Model\Provider\RecaptchaEnterprise;
use Hryvinskyi\InvisibleCaptcha\Model\Verification\VerificationRequest;
use Hryvinskyi\InvisibleCaptcha\Model\Verification\VerificationRequestFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RecaptchaEnterpriseTest extends TestCase
{
    private const CODE = ProviderInterface::CODE_RECAPTCHA_ENTERPRISE;

    /** @var ProviderConfigInterface&MockObject */
    private ProviderConfigInterface $providerConfig;
    /** @var VerifierInterface&MockObject */
    private VerifierInterface $verifier;
    /** @var VerificationRequestFactory&MockObject */
    private VerificationRequestFactory $requestFactory;
    private RecaptchaEnterprise $provider;

    protected function setUp(): void
    {
        $this->providerConfig = $this->createMock(ProviderConfigInterface::class);
        $this->verifier = $this->createMock(VerifierInterface::class);
        $this->requestFactory = $this->createMock(VerificationRequestFactory::class);
        $this->requestFactory->method('create')
            ->willReturnCallback(static fn (): VerificationRequest => new VerificationRequest());

        $this->provider = new RecaptchaEnterprise($this->providerConfig, $this->verifier, $this->requestFactory);
    }

    private function assessmentUrl(string $projectId, string $apiKey): string
    {
        return sprintf(
            'https://recaptchaenterprise.googleapis.com/v1/projects/%s/assessments?key=%s',
            rawurlencode($projectId),
            rawurlencode($apiKey)
        );
    }

    public function testMetadata(): void
    {
        $this->assertSame(self::CODE, $this->provider->getCode());
        $this->assertTrue($this->provider->isScoreBased());
        $this->assertTrue($this->provider->supportsAction());
        $this->assertSame('g-recaptcha-response', $this->provider->getResponseParamName());
        $this->assertSame('https://www.google.com/recaptcha/enterprise.js', $this->provider->getClientScriptUrl());
        $this->assertSame(90000, $this->provider->getTokenTtlMs());
        $this->assertNotSame('', (string)$this->provider->getLabel());
    }

    public function testGetVerifierReturnsInjectedVerifier(): void
    {
        $this->assertSame($this->verifier, $this->provider->getVerifier());
    }

    public function testIsConfiguredTrueWhenSiteSecretAndProjectPresent(): void
    {
        $this->providerConfig->method('getSiteKey')->with(self::CODE, null)->willReturn('SITE');
        $this->providerConfig->method('getSecretKey')->with(self::CODE, null)->willReturn('API-KEY');
        $this->providerConfig->method('getProjectId')->with(self::CODE, null)->willReturn('proj-1');

        $this->assertTrue($this->provider->isConfigured());
    }

    public function testIsConfiguredFalseWhenProjectIdMissing(): void
    {
        $this->providerConfig->method('getSiteKey')->willReturn('SITE');
        $this->providerConfig->method('getSecretKey')->willReturn('API-KEY');
        $this->providerConfig->method('getProjectId')->willReturn('');

        $this->assertFalse($this->provider->isConfigured());
    }

    public function testIsConfiguredFalseWhenSiteMissing(): void
    {
        $this->providerConfig->method('getSiteKey')->willReturn('');

        $this->assertFalse($this->provider->isConfigured());
    }

    public function testGetVerifyUrlBuildsAssessmentsUrlFromProjectAndApiKey(): void
    {
        $this->providerConfig->method('getWidgetOption')->with(self::CODE, 'verify_url', null)->willReturn(null);
        $this->providerConfig->method('getProjectId')->willReturn('my project');
        $this->providerConfig->method('getSecretKey')->willReturn('api/key');

        $this->assertSame(
            $this->assessmentUrl('my project', 'api/key'),
            $this->provider->getVerifyUrl()
        );
    }

    public function testGetVerifyUrlReturnsOverride(): void
    {
        $this->providerConfig->method('getWidgetOption')
            ->with(self::CODE, 'verify_url', null)
            ->willReturn('https://proxy.example/assess');

        $this->assertSame('https://proxy.example/assess', $this->provider->getVerifyUrl());
    }

    public function testGetRenderConfigContainsEnterpriseKeys(): void
    {
        $this->providerConfig->method('getSiteKey')->willReturn('SITE');

        $config = $this->provider->getRenderConfig(null, ['action' => 'checkout']);

        $this->assertSame(self::CODE, $config['provider']);
        $this->assertSame('SITE', $config['siteKey']);
        $this->assertTrue($config['isScoreBased']);
        $this->assertTrue($config['supportsAction']);
        $this->assertSame('checkout', $config['action']);
        $this->assertSame('bottomright', $config['badge']);
        $this->assertSame('score', $config['widgetMode']);
        $this->assertTrue($config['enterprise']);
    }

    public function testCreateVerificationRequestSetsSecretVerifyUrlAndExtra(): void
    {
        $this->providerConfig->method('getSiteKey')->willReturn('SITE');
        $this->providerConfig->method('getSecretKey')->willReturn('API-KEY');
        $this->providerConfig->method('getProjectId')->willReturn('proj-1');
        $this->providerConfig->method('getWidgetOption')->with(self::CODE, 'verify_url', null)->willReturn(null);

        $request = $this->provider->createVerificationRequest();

        $this->assertInstanceOf(VerificationRequest::class, $request);
        $this->assertSame('API-KEY', $request->getSecret());
        $this->assertSame($this->assessmentUrl('proj-1', 'API-KEY'), $request->getVerifyUrl());
        $this->assertSame(['siteKey' => 'SITE', 'projectId' => 'proj-1'], $request->getExtra());
        $this->assertSame('SITE', $request->getExtraValue('siteKey'));
        $this->assertSame('proj-1', $request->getExtraValue('projectId'));
    }
}
