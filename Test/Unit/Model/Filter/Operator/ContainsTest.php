<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Filter\Operator;

use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Operator\Contains;
use PHPUnit\Framework\TestCase;

class ContainsTest extends TestCase
{
    private Contains $operator;

    protected function setUp(): void
    {
        $this->operator = new Contains();
    }

    public function testCodeAndLabel(): void
    {
        $this->assertSame('contains', $this->operator->getCode());
        $this->assertSame('contains', (string)$this->operator->getLabel());
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
            'substring present' => ['hello world', 'world', true],
            'substring missing' => ['hello world', 'foo', false],
            'case sensitive' => ['Hello World', 'world', false],
            'empty needle never matches' => ['hello', '', false],
            'null haystack' => [null, 'x', false],
            'numeric haystack' => [12345, '234', true],
            'full match' => ['exact', 'exact', true],
        ];
    }
}
