<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api;

/**
 * Resolves the score threshold for a protected form. Only consulted when the
 * active provider is score-based (reCAPTCHA v3 / Enterprise).
 */
interface ScoreThresholdInterface
{
    public function getValue(?string $scopeCode = null): float;
}
