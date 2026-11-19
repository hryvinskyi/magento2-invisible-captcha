<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api\Http;

/**
 * The route-gate verification state in Magento's HTTP context, so the full
 * page cache keeps verified and unverified visitors apart.
 */
interface CaptchaContextInterface
{
    /**
     * Set the context value from the incoming request's verification cookie.
     *
     * @return void
     */
    public function setFromCookie(): void;

    /**
     * Force the context to the verified state, so the current response is
     * already keyed as verified.
     *
     * @return void
     */
    public function markVerified(): void;
}
