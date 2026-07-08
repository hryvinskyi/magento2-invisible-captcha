<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api;

/**
 * Ordered list of {@see ConditionInterface} that together form a single
 * filter expression — Cloudflare's "When incoming requests match…" block.
 */
interface ExpressionInterface
{
    /**
     * Conditions in evaluation order.
     *
     * @return ConditionInterface[]
     */
    public function getConditions(): array;

    /**
     * Convenience check — true when the expression has no usable conditions
     * and therefore must never fire a challenge.
     *
     * @return bool
     */
    public function isEmpty(): bool;
}
