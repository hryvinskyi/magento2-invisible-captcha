<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api\RobotsTxt;

/**
 * A single Allow / Disallow line of a robots.txt group.
 *
 * The path is the raw robots.txt pattern: prefix-matched against the request
 * URI, `*` matches any character sequence and a trailing `$` anchors the end.
 */
interface RuleInterface
{
    /**
     * The rule's path pattern as written in robots.txt (e.g. "/checkout/", "/*?price=").
     *
     * @return string
     */
    public function getPath(): string;

    /**
     * Whether this is an Allow rule (true) or a Disallow rule (false).
     *
     * @return bool
     */
    public function isAllow(): bool;
}
