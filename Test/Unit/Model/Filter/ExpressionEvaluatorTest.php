<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Filter;

use Hryvinskyi\InvisibleCaptcha\Api\ConditionInterface;
use Hryvinskyi\InvisibleCaptcha\Api\ExpressionInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldProviderInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Filter\OperatorInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Filter\OperatorProviderInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Condition;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Expression;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\ExpressionEvaluator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Cover the AND/OR precedence algorithm. Each condition is identified by its
 * value; the helper field/operator mocks treat the value as the literal
 * outcome ("true" or "false") so we can assert grouping behavior directly.
 */
class ExpressionEvaluatorTest extends TestCase
{
    /** @var FieldProviderInterface&MockObject */
    private FieldProviderInterface $fieldProvider;
    /** @var OperatorProviderInterface&MockObject */
    private OperatorProviderInterface $operatorProvider;
    private ExpressionEvaluator $evaluator;

    protected function setUp(): void
    {
        $this->fieldProvider = $this->createMock(FieldProviderInterface::class);
        $this->operatorProvider = $this->createMock(OperatorProviderInterface::class);
        $this->evaluator = new ExpressionEvaluator($this->fieldProvider, $this->operatorProvider);

        // Field always resolves to the configured value of the condition.
        $field = $this->createMock(FieldInterface::class);
        $field->method('getValue')->willReturn('whatever');
        $this->fieldProvider->method('get')->willReturn($field);

        // Operator interprets condition value verbatim — "true" => true, "false" => false.
        $operator = $this->createMock(OperatorInterface::class);
        $operator->method('evaluate')->willReturnCallback(
            static fn ($fieldValue, string $configValue): bool => $configValue === 'true'
        );
        $this->operatorProvider->method('get')->willReturn($operator);
    }

    public function testEmptyExpressionReturnsFalse(): void
    {
        $this->assertFalse($this->evaluator->evaluate(new Expression()));
    }

    public function testSingleTrueCondition(): void
    {
        $this->assertTrue($this->evaluator->evaluate($this->expressionOf(['T'])));
    }

    public function testSingleFalseCondition(): void
    {
        $this->assertFalse($this->evaluator->evaluate($this->expressionOf(['F'])));
    }

    /**
     * @dataProvider precedenceProvider
     *
     * Sequences are described as "T and T" / "T or F and T" with the first
     * condition's combinator implicit (treated as AND).
     */
    public function testAndOrPrecedence(string $sequence, bool $expected): void
    {
        $expression = $this->expressionFromSequence($sequence);
        $this->assertSame($expected, $this->evaluator->evaluate($expression), $sequence);
    }

    /**
     * Truth tables for `(A AND B) OR (C AND D)` — AND binds tighter than OR.
     */
    public static function precedenceProvider(): array
    {
        return [
            'AND chain all true'         => ['T and T and T', true],
            'AND chain one false'        => ['T and F and T', false],
            'OR chain — any true wins'   => ['F or T or F',   true],
            'OR chain — all false'       => ['F or F or F',   false],
            'A AND B OR C — first wins'  => ['T and T or F',  true],
            'A AND B OR C — fallback'    => ['T and F or T',  true],
            'A AND B OR C — none'        => ['T and F or F',  false],
            'A OR B AND C — first wins'  => ['T or F and F',  true],
            'A OR B AND C — second wins' => ['F or T and T',  true],
            'A OR B AND C — second mid'  => ['F or T and F',  false],
            'mixed (T AND F) OR (F AND T) OR T' => ['T and F or F and T or T', true],
            'mixed (T AND F) OR (F AND T) OR F' => ['T and F or F and T or F', false],
            'mixed (T AND T) OR (F AND F)' => ['T and T or F and F', true],
        ];
    }

    public function testUnknownFieldOrOperatorCausesConditionToFail(): void
    {
        $fieldProvider = $this->createMock(FieldProviderInterface::class);
        $operatorProvider = $this->createMock(OperatorProviderInterface::class);
        $fieldProvider->method('get')->willReturn(null);
        $operatorProvider->method('get')->willReturn(null);

        $evaluator = new ExpressionEvaluator($fieldProvider, $operatorProvider);

        // Even a "true" condition fails when the operator/field aren't resolvable.
        $expression = new Expression([new Condition('and', 'x', 'y', 'true')]);
        $this->assertFalse($evaluator->evaluate($expression));
    }

    /**
     * Build an expression from a sequence like "T and F or T".
     */
    private function expressionFromSequence(string $sequence): ExpressionInterface
    {
        $tokens = preg_split('/\s+/', trim($sequence)) ?: [];
        $conditions = [];
        $combinator = ConditionInterface::COMBINATOR_AND;

        foreach ($tokens as $token) {
            $lower = strtolower($token);
            if ($lower === 'and' || $lower === 'or') {
                $combinator = $lower;
                continue;
            }
            $value = strtoupper($token) === 'T' ? 'true' : 'false';
            $conditions[] = new Condition($combinator, 'field', 'op', $value);
            $combinator = ConditionInterface::COMBINATOR_AND; // reset
        }

        return new Expression($conditions);
    }

    /**
     * Helper for very simple single-row expressions; truthy = "true", falsy = "false".
     *
     * @param string[] $tokens
     */
    private function expressionOf(array $tokens): ExpressionInterface
    {
        $conditions = [];
        foreach ($tokens as $token) {
            $value = strtoupper($token) === 'T' ? 'true' : 'false';
            $conditions[] = new Condition('and', 'field', 'op', $value);
        }
        return new Expression($conditions);
    }
}
