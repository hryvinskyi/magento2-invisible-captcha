<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Filter\Operator;

use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Operator\NotIn;
use PHPUnit\Framework\TestCase;

class NotInTest extends TestCase
{
    private NotIn $operator;

    protected function setUp(): void
    {
        $this->operator = new NotIn();
    }

    public function testCodeAndLabel(): void
    {
        $this->assertSame('not_in', $this->operator->getCode());
        $this->assertSame('is not in list', (string)$this->operator->getLabel());
    }

    public function testSupportsStringAndNumeric(): void
    {
        $this->assertTrue($this->operator->supports(FieldInterface::TYPE_STRING));
        $this->assertTrue($this->operator->supports(FieldInterface::TYPE_NUMERIC));
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
            'not in list' => ['DELETE', 'GET, POST, PUT', true],
            'in list returns false' => ['POST', 'GET, POST, PUT', false],
            'empty list returns true' => ['anything', '', true],
            'whitespace tolerated' => ['DELETE', '  GET ,  POST  ', true],
        ];
    }
}
