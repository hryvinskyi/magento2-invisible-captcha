<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Filter;

use Hryvinskyi\InvisibleCaptcha\Api\ConditionInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Expression;
use PHPUnit\Framework\TestCase;

class ExpressionTest extends TestCase
{
    public function testEmptyByDefault(): void
    {
        $expression = new Expression();
        $this->assertTrue($expression->isEmpty());
        $this->assertSame([], $expression->getConditions());
    }

    public function testFiltersOutNonConditionEntries(): void
    {
        $valid = $this->createMock(ConditionInterface::class);
        $expression = new Expression([$valid, 'not-a-condition', null, $valid]);

        $conditions = $expression->getConditions();
        $this->assertCount(2, $conditions);
        $this->assertContainsOnlyInstancesOf(ConditionInterface::class, $conditions);
    }

    public function testIsNotEmptyWithValidConditions(): void
    {
        $condition = $this->createMock(ConditionInterface::class);
        $expression = new Expression([$condition]);

        $this->assertFalse($expression->isEmpty());
    }

    public function testValuesArePreservedInOrder(): void
    {
        $a = $this->createMock(ConditionInterface::class);
        $b = $this->createMock(ConditionInterface::class);

        $conditions = (new Expression([$a, $b]))->getConditions();

        $this->assertSame($a, $conditions[0]);
        $this->assertSame($b, $conditions[1]);
    }
}
