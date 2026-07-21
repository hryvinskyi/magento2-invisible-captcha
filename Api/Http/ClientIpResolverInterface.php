<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api\Http;

use Magento\Framework\App\RequestInterface;

/**
 * Resolves the client IP, honoring trusted proxy headers so consumers do not
 * each re-implement the security-relevant policy.
 */
interface ClientIpResolverInterface
{
    /**
     * Best-effort client IP for the current request, honoring trusted proxy
     * headers in priority order (CF-Connecting-IP, X-Real-IP,
     * X-Forwarded-For, REMOTE_ADDR). Empty string when none validates.
     */
    public function resolve(): string;

    /**
     * Same policy as {@see resolve()} but against an explicitly provided
     * request — e.g. the rule tester's synthetic request — instead of the
     * current one.
     *
     * @param RequestInterface $request
     */
    public function resolveFrom(RequestInterface $request): string;
}
