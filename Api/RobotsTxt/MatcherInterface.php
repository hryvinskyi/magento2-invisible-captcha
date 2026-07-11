<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api\RobotsTxt;

/**
 * Decides whether a request URI is disallowed by the store's robots.txt.
 */
interface MatcherInterface
{
    /**
     * Whether robots.txt disallows the given URI for the given user agent.
     *
     * Group selection and rule matching follow RFC 9309 / Google semantics:
     * the most specific user-agent group applies (longest agent token found
     * in the user-agent string, falling back to the `*` group), the longest
     * matching rule path wins, and Allow wins a specificity tie. Missing,
     * empty or unreadable robots.txt never disallows anything (fail-safe).
     *
     * @param string $uri Request URI to test (path with optional query string)
     * @param string $userAgent Raw User-Agent header of the request
     * @return bool
     */
    public function isDisallowed(string $uri, string $userAgent): bool;
}
