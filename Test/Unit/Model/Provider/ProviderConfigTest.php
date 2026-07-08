<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Provider;

use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Provider\ProviderConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProviderConfigTest extends TestCase
{
    /** @var ScopeConfigInterface&MockObject */
    private ScopeConfigInterface $scopeConfig;
    /** @var EncryptorInterface&MockObject */
    private EncryptorInterface $encryptor;
    private ProviderConfig $providerConfig;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->encryptor = $this->createMock(EncryptorInterface::class);

        $this->providerConfig = new ProviderConfig($this->scopeConfig, $this->encryptor);
    }

    public function testGetSiteKeyReadsProviderScopedPath(): void
    {
        $this->scopeConfig->method('getValue')
            ->with(
                'hryvinskyi_invisible_captcha/providers/recaptcha_v3/site_key',
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn('SITE-KEY');

        $this->assertSame('SITE-KEY', $this->providerConfig->getSiteKey(ProviderInterface::CODE_RECAPTCHA_V3));
    }

    public function testGetSiteKeyReturnsEmptyStringWhenUnset(): void
    {
        $this->scopeConfig->method('getValue')->willReturn(null);

        $this->assertSame('', $this->providerConfig->getSiteKey(ProviderInterface::CODE_RECAPTCHA_V3));
    }

    public function testGetWidgetOptionReadsProviderScopedPath(): void
    {
        $this->scopeConfig->method('getValue')
            ->with(
                'hryvinskyi_invisible_captcha/providers/recaptcha_v2_checkbox/theme',
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn('dark');

        $this->assertSame(
            'dark',
            $this->providerConfig->getWidgetOption(ProviderInterface::CODE_RECAPTCHA_V2_CHECKBOX, 'theme')
        );
    }

    public function testGetWidgetOptionReturnsNullWhenUnset(): void
    {
        $this->scopeConfig->method('getValue')->willReturn(null);

        $this->assertNull(
            $this->providerConfig->getWidgetOption(ProviderInterface::CODE_RECAPTCHA_V2_CHECKBOX, 'theme')
        );
    }

    public function testGetSecretKeyDecryptsStoredValue(): void
    {
        $this->scopeConfig->method('getValue')
            ->with(
                'hryvinskyi_invisible_captcha/providers/recaptcha_v3/secret_key',
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn('ENCRYPTED');
        $this->encryptor->expects($this->once())
            ->method('decrypt')
            ->with('ENCRYPTED')
            ->willReturn('PLAIN-SECRET');

        $this->assertSame('PLAIN-SECRET', $this->providerConfig->getSecretKey(ProviderInterface::CODE_RECAPTCHA_V3));
    }

    public function testGetSecretKeyReturnsEmptyStringAndSkipsDecryptWhenUnset(): void
    {
        $this->scopeConfig->method('getValue')->willReturn(null);
        $this->encryptor->expects($this->never())->method('decrypt');

        $this->assertSame('', $this->providerConfig->getSecretKey(ProviderInterface::CODE_RECAPTCHA_V3));
    }

    public function testGetProjectIdReadsProviderScopedPath(): void
    {
        $this->scopeConfig->method('getValue')
            ->with(
                'hryvinskyi_invisible_captcha/providers/recaptcha_enterprise/project_id',
                ScopeInterface::SCOPE_STORE,
                null
            )
            ->willReturn('my-project');

        $this->assertSame(
            'my-project',
            $this->providerConfig->getProjectId(ProviderInterface::CODE_RECAPTCHA_ENTERPRISE)
        );
    }
}
