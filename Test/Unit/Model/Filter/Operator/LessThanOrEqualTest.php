<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Filter\Operator;

use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Operator\LessThanOrEqual;
use PHPUnit\Framework\TestCase;

class LessThanOrEqualTest extends TestCase
{
    private LessThanOrEqual $operator;

    protected function setUp(): void
    {
        $this->operator = new LessThanOrEqual();
    }

    public function testCodeAndLabel(): void
    {
        $this->assertSame('lte', $this->operator->getCode());
        $this->assertSame('less than or equal', (string)$this->operator->getLabel());
    }

    public function testSupportsOnlyNumeric(): void
    {
        $this->assertTrue($this->operator->supports(FieldInterface::TYPE_NUMERIC));
        $this->assertFalse($this->operator->supports(FieldInterface::TYPE_STRING));
    }

    /**
     * @dataProvider evaluateProvider
     */
    public function testEvaluate(string|int|float|null $fieldValue, string $configValue, bool $expected): void
    {
        $this->assertSame($expected, $this->operator->evaluate($fieldValue, $configValue));
    }

    public static function evaluateProvider(): array
    {
        return [
            'less' => [2, '3', true],
            'equal' => [3, '3', true],
            'greater' => [5, '3', false],
            'non-numeric config' => [5, 'abc', false],
        ];
    }
}
