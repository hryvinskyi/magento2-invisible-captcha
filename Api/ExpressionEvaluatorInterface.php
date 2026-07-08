<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api;

/**
 * Evaluates an {@see ExpressionInterface} against the current request.
 *
 * Evaluation uses standard precedence — AND binds tighter than OR — so a
 * flat list `A AND B OR C AND D` becomes `(A AND B) OR (C AND D)`, matching
 * how Cloudflare's expression editor compiles its list view.
 */
interface ExpressionEvaluatorInterface
{
    /**
     * @param ExpressionInterface $expression
     * @return bool True when the expression matches the current request.
     */
    public function evaluate(ExpressionInterface $expression): bool;
}
