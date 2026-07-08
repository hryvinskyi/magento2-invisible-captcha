<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Filter\Operator;

use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Operator\EndsWith;
use PHPUnit\Framework\TestCase;

class EndsWithTest extends TestCase
{
    private EndsWith $operator;

    protected function setUp(): void
    {
        $this->operator = new EndsWith();
    }

    public function testCodeAndLabel(): void
    {
        $this->assertSame('ends_with', $this->operator->getCode());
        $this->assertSame('ends with', (string)$this->operator->getLabel());
    }

    public function testSupportsOnlyString(): void
    {
        $this->assertTrue($this->operator->supports(FieldInterface::TYPE_STRING));
        $this->assertFalse($this->operator->supports(FieldInterface::TYPE_NUMERIC));
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
            'suffix present' => ['profile.html', '.html', true],
            'suffix missing' => ['profile.html', '.htm', false],
            'empty needle never matches' => ['anything', '', false],
            'null haystack' => [null, 'x', false],
            'exact match' => ['x', 'x', true],
        ];
    }
}
