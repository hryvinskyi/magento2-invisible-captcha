<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api\RobotsTxt;

/**
 * Parses raw robots.txt content into user-agent groups (RFC 9309 grammar,
 * parsed leniently — unknown directives and malformed lines are skipped).
 */
interface ParserInterface
{
    /**
     * Parse robots.txt content into its user-agent groups.
     *
     * @param string $content Raw robots.txt body
     * @return GroupInterface[]
     */
    public function parse(string $content): array;
}
