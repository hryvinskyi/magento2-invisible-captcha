<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Filter;

use Hryvinskyi\InvisibleCaptcha\Api\ConditionInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Condition;
use PHPUnit\Framework\TestCase;

class ConditionTest extends TestCase
{
    public function testGettersExposeConstructorValues(): void
    {
        $condition = new Condition('and', 'action_name', 'eq', 'catalog_category_view');

        $this->assertSame(ConditionInterface::COMBINATOR_AND, $condition->getCombinator());
        $this->assertSame('action_name', $condition->getFieldCode());
        $this->assertSame('eq', $condition->getOperatorCode());
        $this->assertSame('catalog_category_view', $condition->getValue());
    }

    /**
     * @dataProvider combinatorProvider
     */
    public function testCombinatorNormalization(string $input, string $expected): void
    {
        $condition = new Condition($input, 'field', 'op', 'value');
        $this->assertSame($expected, $condition->getCombinator());
    }

    public static function combinatorProvider(): array
    {
        return [
            'lowercase or' => ['or', ConditionInterface::COMBINATOR_OR],
            'uppercase OR' => ['OR', ConditionInterface::COMBINATOR_OR],
            'mixed case Or' => ['Or', ConditionInterface::COMBINATOR_OR],
            'and is default' => ['and', ConditionInterface::COMBINATOR_AND],
            'garbage falls back to and' => ['xor', ConditionInterface::COMBINATOR_AND],
            'empty falls back to and' => ['', ConditionInterface::COMBINATOR_AND],
        ];
    }
}
