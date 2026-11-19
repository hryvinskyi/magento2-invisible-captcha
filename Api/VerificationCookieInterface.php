<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;

/**
 * The signed cookie that marks a visitor as having passed the route-level
 * challenge.
 */
interface VerificationCookieInterface
{
    /**
     * Whether the current request carries a valid, unexpired verification cookie.
     *
     * @return bool
     */
    public function isVerified(): bool;

    /**
     * Issue a fresh verification cookie to the client.
     *
     * @return void
     * @throws CookieSizeLimitReachedException
     * @throws FailureToSendException
     * @throws InputException
     */
    public function setVerified(): void;

    /**
     * Remove the verification cookie from the client.
     *
     * @return void
     * @throws FailureToSendException
     * @throws InputException
     */
    public function deleteCookie(): void;
}
