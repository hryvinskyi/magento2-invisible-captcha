<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api;

/**
 * Route-protection bypass lists: decides whether a client IP or user agent
 * is excluded from the challenge. Shared by the live gate
 * ({@see \Hryvinskyi\InvisibleCaptcha\Model\RequestChecker}) and the admin
 * rule tester so both apply identical semantics.
 */
interface ExclusionPolicyInterface
{
    /**
     * Whether the IP is on the "Excluded IPs" list (exact match).
     *
     * @param string $ip Resolved client IP; an empty string is never excluded
     * @param string|null $scopeCode Store scope to read the list from (null = current)
     * @return bool
     */
    public function isIpExcluded(string $ip, ?string $scopeCode = null): bool;

    /**
     * Whether the user agent matches any "Excluded User Agents" substring
     * (case-insensitive).
     *
     * @param string $userAgent Raw User-Agent header; an empty string is never excluded
     * @param string|null $scopeCode Store scope to read the list from (null = current)
     * @return bool
     */
    public function isUserAgentExcluded(string $userAgent, ?string $scopeCode = null): bool;

    /**
     * Whether the request path matches any "Excluded Paths" pattern
     * (robots.txt-style: prefix match, `*` wildcard, trailing `$` anchor).
     *
     * @param string $path URI path without query string; leading slash optional
     * @param string|null $scopeCode Store scope to read the list from (null = current)
     * @return bool
     */
    public function isPathExcluded(string $path, ?string $scopeCode = null): bool;
}
