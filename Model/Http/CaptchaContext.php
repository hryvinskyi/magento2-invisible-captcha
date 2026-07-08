<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Http;

use Hryvinskyi\InvisibleCaptcha\Model\CookieManager;
use Magento\Framework\App\Http\Context;

/**
 * Adds the route-gate verification state to Magento's HTTP context so Varnish
 * keeps verified and unverified visitors in separate cache buckets.
 */
class CaptchaContext
{
    public const CONTEXT_KEY = 'CAPTCHA_VERIFIED';
    public const VALUE_VERIFIED = '1';
    public const VALUE_UNVERIFIED = '0';

    /**
     * @param CookieManager $cookieManager
     * @param Context $httpContext
     */
    public function __construct(
        private readonly CookieManager $cookieManager,
        private readonly Context $httpContext
    ) {
    }

    /**
     * Populate the HTTP context value from the incoming request's cookie state.
     */
    public function setFromCookie(): void
    {
        $value = $this->cookieManager->isVerified() ? self::VALUE_VERIFIED : self::VALUE_UNVERIFIED;
        $this->httpContext->setValue(self::CONTEXT_KEY, $value, self::VALUE_UNVERIFIED);
    }

    /**
     * Force the HTTP context to the verified state so X-Magento-Vary on the
     * current response already reflects post-verification cache keying.
     */
    public function markVerified(): void
    {
        $this->httpContext->setValue(self::CONTEXT_KEY, self::VALUE_VERIFIED, self::VALUE_UNVERIFIED);
    }
}
