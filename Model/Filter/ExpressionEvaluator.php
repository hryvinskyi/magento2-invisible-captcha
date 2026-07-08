<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Filter;

use Hryvinskyi\InvisibleCaptcha\Api\ConditionInterface;
use Hryvinskyi\InvisibleCaptcha\Api\ExpressionEvaluatorInterface;
use Hryvinskyi\InvisibleCaptcha\Api\ExpressionInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldProviderInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Filter\OperatorProviderInterface;

class ExpressionEvaluator implements ExpressionEvaluatorInterface
{
    /**
     * @param FieldProviderInterface $fieldProvider
     * @param OperatorProviderInterface $operatorProvider
     */
    public function __construct(
        private readonly FieldProviderInterface $fieldProvider,
        private readonly OperatorProviderInterface $operatorProvider
    ) {
    }

    /**
     * @inheritDoc
     *
     * Conditions are split into groups at every `or` combinator (so AND
     * binds tighter than OR, matching standard boolean precedence and
     * Cloudflare's evaluation). The expression matches when at least one
     * group has every condition true.
     */
    public function evaluate(ExpressionInterface $expression): bool
    {
        if ($expression->isEmpty()) {
            return false;
        }

        $groups = $this->splitIntoAndGroups($expression->getConditions());

        foreach ($groups as $group) {
            if ($this->evaluateAndGroup($group)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Group consecutive AND-joined conditions; a new group starts at each OR.
     *
     * @param ConditionInterface[] $conditions
     * @return array<int, ConditionInterface[]>
     */
    private function splitIntoAndGroups(array $conditions): array
    {
        $groups = [[]];

        foreach ($conditions as $index => $condition) {
            if ($index > 0 && $condition->getCombinator() === ConditionInterface::COMBINATOR_OR) {
                $groups[] = [];
            }
            $groups[array_key_last($groups)][] = $condition;
        }

        return array_values(array_filter($groups, static fn (array $group): bool => $group !== []));
    }

    /**
     * Evaluate an AND group — all conditions must be true.
     *
     * @param ConditionInterface[] $group
     * @return bool
     */
    private function evaluateAndGroup(array $group): bool
    {
        foreach ($group as $condition) {
            if (!$this->evaluateCondition($condition)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Resolve the field, fetch the operator, and apply it. Missing/invalid
     * field or operator codes evaluate to false so a single bad row cannot
     * mask the rest of the expression.
     *
     * @param ConditionInterface $condition
     * @return bool
     */
    private function evaluateCondition(ConditionInterface $condition): bool
    {
        $field = $this->fieldProvider->get($condition->getFieldCode());
        $operator = $this->operatorProvider->get($condition->getOperatorCode());

        if ($field === null || $operator === null) {
            return false;
        }

        return $operator->evaluate($field->getValue(), $condition->getValue());
    }
}
