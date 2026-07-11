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
use Hryvinskyi\InvisibleCaptcha\Api\ExpressionTracerInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldProviderInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Filter\OperatorProviderInterface;

class ExpressionEvaluator implements ExpressionEvaluatorInterface, ExpressionTracerInterface
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
     * @inheritDoc
     *
     * Unlike {@see evaluate()}, nothing short-circuits: every condition of
     * every group is resolved so the caller can display the full picture.
     */
    public function trace(ExpressionInterface $expression, ?FieldProviderInterface $fieldProvider = null): array
    {
        $fieldProvider ??= $this->fieldProvider;

        $matched = false;
        $groups = [];
        foreach ($this->splitIntoAndGroups($expression->getConditions()) as $group) {
            $groupMatched = true;
            $conditions = [];
            foreach ($group as $condition) {
                $conditionTrace = $this->traceCondition($condition, $fieldProvider);
                $groupMatched = $groupMatched && $conditionTrace['matched'];
                $conditions[] = $conditionTrace;
            }

            $matched = $matched || $groupMatched;
            $groups[] = ['matched' => $groupMatched, 'conditions' => $conditions];
        }

        return ['matched' => $groups !== [] && $matched, 'groups' => $groups];
    }

    /**
     * Resolve one condition with full diagnostics: the field's actual value,
     * whether field and operator codes were resolvable, and the outcome.
     *
     * @param ConditionInterface $condition
     * @param FieldProviderInterface $fieldProvider
     * @return array{
     *     combinator: string,
     *     field: string,
     *     operator: string,
     *     value: string,
     *     fieldValue: string|int|float|null,
     *     known: bool,
     *     matched: bool
     * }
     */
    private function traceCondition(ConditionInterface $condition, FieldProviderInterface $fieldProvider): array
    {
        $field = $fieldProvider->get($condition->getFieldCode());
        $operator = $this->operatorProvider->get($condition->getOperatorCode());
        $known = $field !== null && $operator !== null;
        $fieldValue = $field?->getValue();

        return [
            'combinator' => $condition->getCombinator(),
            'field' => $condition->getFieldCode(),
            'operator' => $condition->getOperatorCode(),
            'value' => $condition->getValue(),
            'fieldValue' => $fieldValue,
            'known' => $known,
            'matched' => $known && $operator->evaluate($fieldValue, $condition->getValue()),
        ];
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
