<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Filter\Operator;

use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Operator\StartsWith;
use PHPUnit\Framework\TestCase;

class StartsWithTest extends TestCase
{
    private StartsWith $operator;

    protected function setUp(): void
    {
        $this->operator = new StartsWith();
    }

    public function testCodeAndLabel(): void
    {
        $this->assertSame('starts_with', $this->operator->getCode());
        $this->assertSame('starts with', (string)$this->operator->getLabel());
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
            'prefix present' => ['/checkout/onepage', '/checkout', true],
            'middle is not prefix' => ['/customer/checkout', '/checkout', false],
            'empty needle never matches' => ['anything', '', false],
            'null haystack' => [null, '/x', false],
            'case sensitive' => ['/Checkout', '/checkout', false],
            'exact match' => ['exact', 'exact', true],
        ];
    }
}
