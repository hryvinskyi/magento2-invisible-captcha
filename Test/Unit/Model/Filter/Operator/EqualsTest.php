<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Filter\Operator;

use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Operator\Equals;
use PHPUnit\Framework\TestCase;

class EqualsTest extends TestCase
{
    private Equals $operator;

    protected function setUp(): void
    {
        $this->operator = new Equals();
    }

    public function testCodeAndLabel(): void
    {
        $this->assertSame('eq', $this->operator->getCode());
        $this->assertSame('equals', (string)$this->operator->getLabel());
    }

    public function testSupportsStringAndNumeric(): void
    {
        $this->assertTrue($this->operator->supports(FieldInterface::TYPE_STRING));
        $this->assertTrue($this->operator->supports(FieldInterface::TYPE_NUMERIC));
        $this->assertTrue($this->operator->supports(FieldInterface::TYPE_BOOLEAN));
        $this->assertFalse($this->operator->supports('unknown'));
    }

    /**
     * @dataProvider evaluateProvider
     */
    public function testEvaluate(string|int|float|null $fieldValue, string $configValue, bool $expected): void
    {
        $this->assertSame($expected, $this->operator->evaluate($fieldValue, $configValue));
    }

    /**
     * @return array<string, array{0: string|int|float|null, 1: string, 2: bool}>
     */
    public static function evaluateProvider(): array
    {
        return [
            'exact string match' => ['hello', 'hello', true],
            'string mismatch' => ['hello', 'world', false],
            'case sensitive' => ['Hello', 'hello', false],
            'numeric string match' => ['42', '42', true],
            'int coerced to string' => [42, '42', true],
            'float coerced' => [3.14, '3.14', true],
            'null treated as empty' => [null, '', true],
            'null vs non-empty' => [null, 'x', false],
            'empty string vs empty' => ['', '', true],
        ];
    }
}
