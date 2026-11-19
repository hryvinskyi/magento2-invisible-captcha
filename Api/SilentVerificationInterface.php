<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api;

/**
 * Background verification of an unverified visitor on a page route protection
 * does not gate, so a visitor who browses on to a protected route already holds
 * the verification cookie and never sees the challenge page.
 *
 * The browser asks the route-gate provider for a token without any interaction
 * and posts it to the verify endpoint, which issues the cookie as it does for
 * the challenge page. Only providers that can issue a token invisibly take
 * part; a visitor the provider cannot verify that way is left alone and meets
 * the challenge page on a protected route as before.
 */
interface SilentVerificationInterface
{
    /**
     * Whether the current page carries the background verification: it is
     * enabled, route protection is configured, the route-gate provider can
     * verify invisibly, and the visitor is not verified yet.
     *
     * @return bool
     */
    public function isActive(): bool;

    /**
     * What the browser needs to verify in the background.
     *
     * Keys: `verifyUrl`, `mode` (how the provider issues a token invisibly:
     * `widget` or `score`), `provider`, `siteKey`, `responseParam`,
     * `scriptUrl`, `render` (the provider's render options).
     *
     * @return array<string, mixed>
     */
    public function getClientConfig(): array;
}
