<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Filter;

use Hryvinskyi\InvisibleCaptcha\Api\ConditionInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldProviderInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Filter\OperatorInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Filter\OperatorProviderInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Condition;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\ConditionFactory;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Expression;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\ExpressionFactory;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\ExpressionParser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ExpressionParserTest extends TestCase
{
    /** @var ConditionFactory&MockObject */
    private ConditionFactory $conditionFactory;
    /** @var ExpressionFactory&MockObject */
    private ExpressionFactory $expressionFactory;
    /** @var FieldProviderInterface&MockObject */
    private FieldProviderInterface $fieldProvider;
    /** @var OperatorProviderInterface&MockObject */
    private OperatorProviderInterface $operatorProvider;
    private ExpressionParser $parser;

    protected function setUp(): void
    {
        $this->conditionFactory = $this->getMockBuilder(ConditionFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->expressionFactory = $this->getMockBuilder(ExpressionFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->fieldProvider = $this->createMock(FieldProviderInterface::class);
        $this->operatorProvider = $this->createMock(OperatorProviderInterface::class);

        $this->parser = new ExpressionParser(
            $this->conditionFactory,
            $this->expressionFactory,
            $this->fieldProvider,
            $this->operatorProvider
        );

        // Make factory.create() return a real Condition with the data given.
        $this->conditionFactory->method('create')->willReturnCallback(
            static fn (array $data): Condition => new Condition(
                $data['combinator'],
                $data['fieldCode'],
                $data['operatorCode'],
                $data['value']
            )
        );
        $this->expressionFactory->method('create')->willReturnCallback(
            static fn (array $data): Expression => new Expression($data['conditions'])
        );

        // Treat any registered field/operator as known.
        $field = $this->createMock(FieldInterface::class);
        $operator = $this->createMock(OperatorInterface::class);
        $this->fieldProvider->method('get')->willReturn($field);
        $this->operatorProvider->method('get')->willReturn($operator);
    }

    public function testEmptyRowsProduceEmptyExpression(): void
    {
        $expression = $this->parser->parse([]);
        $this->assertTrue($expression->isEmpty());
    }

    public function testSkipsRowsWithMissingFieldOrOperator(): void
    {
        $rows = [
            ['combinator' => 'and', 'field' => '', 'operator' => 'eq', 'value' => 'x'],
            ['combinator' => 'and', 'field' => 'action_name', 'operator' => '', 'value' => 'x'],
            ['combinator' => 'and'],
            'not-an-array',
        ];

        $expression = $this->parser->parse($rows);
        $this->assertTrue($expression->isEmpty());
    }

    public function testSkipsRowsWithUnknownCodes(): void
    {
        $fieldProvider = $this->createMock(FieldProviderInterface::class);
        $operatorProvider = $this->createMock(OperatorProviderInterface::class);
        $fieldProvider->method('get')->willReturn(null);
        $operatorProvider->method('get')->willReturn(null);

        $parser = new ExpressionParser(
            $this->conditionFactory,
            $this->expressionFactory,
            $fieldProvider,
            $operatorProvider
        );

        $expression = $parser->parse([
            ['combinator' => 'and', 'field' => 'unknown_field', 'operator' => 'eq', 'value' => 'x'],
        ]);

        $this->assertTrue($expression->isEmpty());
    }

    public function testProducesConditionsForValidRows(): void
    {
        $rows = [
            ['combinator' => 'and', 'field' => 'action_name', 'operator' => 'eq', 'value' => 'home'],
            ['combinator' => 'or',  'field' => 'uri_path',    'operator' => 'starts_with', 'value' => '/checkout'],
        ];

        $conditions = $this->parser->parse($rows)->getConditions();

        $this->assertCount(2, $conditions);
        $this->assertSame(ConditionInterface::COMBINATOR_AND, $conditions[0]->getCombinator());
        $this->assertSame('action_name', $conditions[0]->getFieldCode());
        $this->assertSame('eq', $conditions[0]->getOperatorCode());
        $this->assertSame('home', $conditions[0]->getValue());

        $this->assertSame(ConditionInterface::COMBINATOR_OR, $conditions[1]->getCombinator());
        $this->assertSame('/checkout', $conditions[1]->getValue());
    }

    public function testTrimWhitespaceAroundCodes(): void
    {
        $rows = [
            ['combinator' => 'and', 'field' => '  action_name  ', 'operator' => '  eq  ', 'value' => 'x'],
        ];
        $conditions = $this->parser->parse($rows)->getConditions();
        $this->assertSame('action_name', $conditions[0]->getFieldCode());
        $this->assertSame('eq', $conditions[0]->getOperatorCode());
    }
}
