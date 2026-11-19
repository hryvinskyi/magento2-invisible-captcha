<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api;

/**
 * Short opaque correlation tokens that follow a challenge through the inline
 * render, the verify POST, and both ends of the log trail.
 */
interface RefIdGeneratorInterface
{
    /**
     * A fresh token.
     *
     * @return string
     */
    public function generate(): string;

    /**
     * The token formatted for display; empty when it is not a valid token.
     *
     * @param string $token
     * @return string
     */
    public function format(string $token): string;

    /**
     * Whether the value looks like a token {@see generate()} could have produced.
     *
     * @param string $token
     * @return bool
     */
    public function isValid(string $token): bool;
}
