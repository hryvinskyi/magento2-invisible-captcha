<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Http;

use Hryvinskyi\InvisibleCaptcha\Model\CookieManager;
use Hryvinskyi\InvisibleCaptcha\Model\Http\CaptchaContext;
use Magento\Framework\App\Http\Context;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CaptchaContextTest extends TestCase
{
    private const CONTEXT_KEY = 'CAPTCHA_VERIFIED';
    private const VALUE_VERIFIED = '1';
    private const VALUE_UNVERIFIED = '0';

    /** @var CookieManager&MockObject */
    private CookieManager $cookieManager;
    /** @var Context&MockObject */
    private Context $httpContext;
    private CaptchaContext $model;

    protected function setUp(): void
    {
        $this->cookieManager = $this->createMock(CookieManager::class);
        $this->httpContext = $this->createMock(Context::class);

        $this->model = new CaptchaContext($this->cookieManager, $this->httpContext);
    }

    public function testSetFromCookieWritesVerifiedWhenCookieValid(): void
    {
        $this->cookieManager->method('isVerified')->willReturn(true);

        $this->httpContext->expects($this->once())->method('setValue')
            ->with(self::CONTEXT_KEY, self::VALUE_VERIFIED, self::VALUE_UNVERIFIED);

        $this->model->setFromCookie();
    }

    public function testSetFromCookieWritesUnverifiedWhenCookieInvalid(): void
    {
        $this->cookieManager->method('isVerified')->willReturn(false);

        $this->httpContext->expects($this->once())->method('setValue')
            ->with(self::CONTEXT_KEY, self::VALUE_UNVERIFIED, self::VALUE_UNVERIFIED);

        $this->model->setFromCookie();
    }

    public function testMarkVerifiedForcesVerifiedState(): void
    {
        // markVerified does not consult the cookie at all.
        $this->cookieManager->expects($this->never())->method('isVerified');

        $this->httpContext->expects($this->once())->method('setValue')
            ->with(self::CONTEXT_KEY, self::VALUE_VERIFIED, self::VALUE_UNVERIFIED);

        $this->model->markVerified();
    }
}
