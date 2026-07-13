<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api;

/**
 * Robots.txt-style path pattern matching, shared by the robots.txt rule
 * matcher and the Excluded Paths bypass: patterns are prefix-matched, `*`
 * matches any character sequence and a trailing `$` anchors the end.
 */
interface PathPatternMatcherInterface
{
    /**
     * Whether the pattern matches the path.
     *
     * @param string $pattern Robots.txt-style path pattern (e.g. "/checkout/", "/*.pdf$")
     * @param string $path Path to test, with a leading slash
     * @return bool
     */
    public function matches(string $pattern, string $path): bool;

    /**
     * Whether any of the patterns matches the path.
     *
     * @param string[] $patterns
     * @param string $path
     * @return bool
     */
    public function matchesAny(array $patterns, string $path): bool;
}
