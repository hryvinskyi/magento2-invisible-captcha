<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Filter;

use Hryvinskyi\InvisibleCaptcha\Api\ConditionInterface;
use Hryvinskyi\InvisibleCaptcha\Api\ExpressionInterface;
use Hryvinskyi\InvisibleCaptcha\Api\ExpressionParserInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldProviderInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Filter\OperatorProviderInterface;

class ExpressionParser implements ExpressionParserInterface
{
    /**
     * @param ConditionFactory $conditionFactory
     * @param ExpressionFactory $expressionFactory
     * @param FieldProviderInterface $fieldProvider
     * @param OperatorProviderInterface $operatorProvider
     */
    public function __construct(
        private readonly ConditionFactory $conditionFactory,
        private readonly ExpressionFactory $expressionFactory,
        private readonly FieldProviderInterface $fieldProvider,
        private readonly OperatorProviderInterface $operatorProvider
    ) {
    }

    /**
     * @inheritDoc
     */
    public function parse(array $rows): ExpressionInterface
    {
        $conditions = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $fieldCode = trim((string)($row['field'] ?? ''));
            $operatorCode = trim((string)($row['operator'] ?? ''));
            if ($fieldCode === '' || $operatorCode === '') {
                continue;
            }
            if ($this->fieldProvider->get($fieldCode) === null) {
                continue;
            }
            if ($this->operatorProvider->get($operatorCode) === null) {
                continue;
            }

            $conditions[] = $this->conditionFactory->create([
                'combinator' => (string)($row['combinator'] ?? ConditionInterface::COMBINATOR_AND),
                'fieldCode' => $fieldCode,
                'operatorCode' => $operatorCode,
                'value' => (string)($row['value'] ?? ''),
            ]);
        }

        return $this->expressionFactory->create(['conditions' => $conditions]);
    }
}
