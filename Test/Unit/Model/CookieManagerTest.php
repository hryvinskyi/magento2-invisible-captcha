<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Model\CookieManager;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Magento\Framework\Stdlib\CookieManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CookieManagerTest extends TestCase
{
    private const COOKIE_NAME = 'hryvinskyi_captcha_verified';
    private const CRYPT_KEY = 'unit-test-crypt-key';

    /** @var CookieManagerInterface&MockObject */
    private CookieManagerInterface $cookieManager;
    /** @var CookieMetadataFactory&MockObject */
    private CookieMetadataFactory $cookieMetadataFactory;
    /** @var ConfigInterface&MockObject */
    private ConfigInterface $config;
    /** @var DeploymentConfig&MockObject */
    private DeploymentConfig $deploymentConfig;
    private CookieManager $model;

    protected function setUp(): void
    {
        $this->cookieManager = $this->createMock(CookieManagerInterface::class);
        $this->cookieMetadataFactory = $this->createMock(CookieMetadataFactory::class);
        $this->config = $this->createMock(ConfigInterface::class);
        $this->deploymentConfig = $this->createMock(DeploymentConfig::class);

        $this->model = new CookieManager(
            $this->cookieManager,
            $this->cookieMetadataFactory,
            $this->config,
            $this->deploymentConfig
        );
    }

    public function testIsVerifiedFalseWhenCookieMissing(): void
    {
        $this->cookieManager->method('getCookie')->with(self::COOKIE_NAME, '')->willReturn('');
        // The signing key is never read when there is nothing to validate.
        $this->deploymentConfig->expects($this->never())->method('get');

        $this->assertFalse($this->model->isVerified());
    }

    public function testIsVerifiedFalseWhenCookieMalformed(): void
    {
        // No ":" separator -> only one part.
        $this->cookieManager->method('getCookie')->willReturn('no-separator-value');

        $this->assertFalse($this->model->isVerified());
    }

    public function testIsVerifiedFalseWhenTimestampNotNumeric(): void
    {
        $this->cookieManager->method('getCookie')->willReturn('notdigits:somehmac');

        $this->assertFalse($this->model->isVerified());
    }

    public function testIsVerifiedFalseWhenExpired(): void
    {
        $expired = time() - 60;
        $this->cookieManager->method('getCookie')
            ->willReturn($this->signedCookie($expired));

        $this->assertFalse($this->model->isVerified());
    }

    public function testIsVerifiedFalseWhenHmacInvalid(): void
    {
        $expiry = time() + 3600;
        $this->deploymentConfig->method('get')->willReturn(self::CRYPT_KEY);
        $this->cookieManager->method('getCookie')
            ->willReturn($expiry . ':' . str_repeat('0', 64));

        $this->assertFalse($this->model->isVerified());
    }

    public function testIsVerifiedTrueForValidCookie(): void
    {
        $expiry = time() + 3600;
        $this->deploymentConfig->method('get')
            ->with(ConfigOptionsListConstants::CONFIG_PATH_CRYPT_KEY)
            ->willReturn(self::CRYPT_KEY);
        $this->cookieManager->method('getCookie')
            ->willReturn($this->signedCookie($expiry, self::CRYPT_KEY));

        $this->assertTrue($this->model->isVerified());
    }

    public function testIsVerifiedUsesLatestNewlineSeparatedCryptKey(): void
    {
        $expiry = time() + 3600;
        // crypt/key may hold rotated keys; the last (newest) one is canonical.
        $this->deploymentConfig->method('get')->willReturn("old-key\nnew-key");
        $this->cookieManager->method('getCookie')
            ->willReturn($this->signedCookie($expiry, 'new-key'));

        $this->assertTrue($this->model->isVerified());
    }

    public function testSetVerifiedIssuesSignedCookieWithHardenedMetadata(): void
    {
        $this->config->method('getCookieLifetime')->willReturn(3600);
        $this->deploymentConfig->method('get')->willReturn(self::CRYPT_KEY);

        $metadata = $this->createMock(PublicCookieMetadata::class);
        $this->cookieMetadataFactory->method('createPublicCookieMetadata')->willReturn($metadata);

        $metadata->expects($this->once())->method('setDuration')->with(3600)->willReturnSelf();
        $metadata->expects($this->once())->method('setPath')->with('/')->willReturnSelf();
        $metadata->expects($this->once())->method('setHttpOnly')->with(true)->willReturnSelf();
        $metadata->expects($this->once())->method('setSecure')->with(true)->willReturnSelf();
        $metadata->expects($this->once())->method('setSameSite')->with('Strict')->willReturnSelf();

        $this->cookieManager->expects($this->once())->method('setPublicCookie')->with(
            self::COOKIE_NAME,
            $this->matchesRegularExpression('/^\d+:[0-9a-f]{64}$/'),
            $metadata
        );

        $this->model->setVerified();
    }

    public function testDeleteCookieRemovesCookieAtRootPath(): void
    {
        $metadata = $this->createMock(PublicCookieMetadata::class);
        $this->cookieMetadataFactory->method('createPublicCookieMetadata')->willReturn($metadata);
        $metadata->expects($this->once())->method('setPath')->with('/')->willReturnSelf();

        $this->cookieManager->expects($this->once())->method('deleteCookie')
            ->with(self::COOKIE_NAME, $metadata);

        $this->model->deleteCookie();
    }

    /**
     * Build a cookie value matching the model's "expiry:hmac" wire format.
     */
    private function signedCookie(int $expiry, string $key = self::CRYPT_KEY): string
    {
        return $expiry . ':' . hash_hmac('sha256', (string)$expiry, $key);
    }
}
