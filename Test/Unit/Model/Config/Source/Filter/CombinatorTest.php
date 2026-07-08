<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Config\Source\Filter;

use Hryvinskyi\InvisibleCaptcha\Api\ConditionInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Config\Source\Filter\Combinator;
use PHPUnit\Framework\TestCase;

class CombinatorTest extends TestCase
{
    public function testToOptionArrayReturnsAndOrCombinators(): void
    {
        $options = (new Combinator())->toOptionArray();

        $this->assertCount(2, $options);

        $this->assertSame(ConditionInterface::COMBINATOR_AND, $options[0]['value']);
        $this->assertSame('AND', (string)$options[0]['label']);

        $this->assertSame(ConditionInterface::COMBINATOR_OR, $options[1]['value']);
        $this->assertSame('OR', (string)$options[1]['label']);
    }
}
