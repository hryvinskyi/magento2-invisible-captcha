<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Filter\Operator;

use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Operator\NotEquals;
use PHPUnit\Framework\TestCase;

class NotEqualsTest extends TestCase
{
    private NotEquals $operator;

    protected function setUp(): void
    {
        $this->operator = new NotEquals();
    }

    public function testCodeAndLabel(): void
    {
        $this->assertSame('neq', $this->operator->getCode());
        $this->assertSame('does not equal', (string)$this->operator->getLabel());
    }

    public function testSupportsStringAndNumeric(): void
    {
        $this->assertTrue($this->operator->supports(FieldInterface::TYPE_STRING));
        $this->assertTrue($this->operator->supports(FieldInterface::TYPE_NUMERIC));
        $this->assertTrue($this->operator->supports(FieldInterface::TYPE_BOOLEAN));
        $this->assertFalse($this->operator->supports('other'));
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
            'exact match' => ['hello', 'hello', false],
            'mismatch' => ['hello', 'world', true],
            'null vs empty' => [null, '', false],
            'null vs non-empty' => [null, 'x', true],
        ];
    }
}
