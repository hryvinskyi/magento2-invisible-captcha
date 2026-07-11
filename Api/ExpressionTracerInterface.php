<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api;

use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldProviderInterface;

/**
 * Diagnostic twin of {@see ExpressionEvaluatorInterface}: evaluates every
 * condition (no short-circuiting) and reports the outcome per condition and
 * per AND-group, using the same AND/OR precedence as the live evaluator.
 */
interface ExpressionTracerInterface
{
    /**
     * Evaluate the expression and return the full evaluation trace.
     *
     * @param ExpressionInterface $expression
     * @param FieldProviderInterface|null $fieldProvider Field registry to resolve
     *        values from — pass a simulated registry to evaluate against a
     *        synthetic request; null uses the live registry
     * @return array{
     *     matched: bool,
     *     groups: array<int, array{
     *         matched: bool,
     *         conditions: array<int, array{
     *             combinator: string,
     *             field: string,
     *             operator: string,
     *             value: string,
     *             fieldValue: string|int|float|null,
     *             known: bool,
     *             matched: bool
     *         }>
     *     }>
     * }
     */
    public function trace(ExpressionInterface $expression, ?FieldProviderInterface $fieldProvider = null): array;
}
