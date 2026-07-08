<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model;

/**
 * Issues short opaque correlation tokens that follow a challenge through the
 * inline render, the verify POST, and both ends of the log trail.
 */
class RefIdGenerator
{
    private const TOKEN_BYTES = 4;
    private const TOKEN_PATTERN = '/^[A-Z0-9]{8}$/';

    /**
     * Generate a fresh 8-character hex token, e.g. "A7F23K9M".
     */
    public function generate(): string
    {
        try {
            return strtoupper(bin2hex(random_bytes(self::TOKEN_BYTES)));
        } catch (\Throwable) {
            return strtoupper(substr(md5((string)microtime(true)), 0, self::TOKEN_BYTES * 2));
        }
    }

    /**
     * Format a raw token for visual display as "A7F2 · 3K9M".
     */
    public function format(string $token): string
    {
        if (!$this->isValid($token)) {
            return '';
        }

        return substr($token, 0, 4) . ' · ' . substr($token, 4, 4);
    }

    /**
     * Validate that a value looks like a token this generator could have produced.
     */
    public function isValid(string $token): bool
    {
        return preg_match(self::TOKEN_PATTERN, $token) === 1;
    }
}
