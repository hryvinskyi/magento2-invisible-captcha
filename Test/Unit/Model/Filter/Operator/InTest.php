<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Filter\Operator;

use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Operator\In;
use PHPUnit\Framework\TestCase;

class InTest extends TestCase
{
    private In $operator;

    protected function setUp(): void
    {
        $this->operator = new In();
    }

    public function testCodeAndLabel(): void
    {
        $this->assertSame('in', $this->operator->getCode());
        $this->assertSame('is in list', (string)$this->operator->getLabel());
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
            'comma list match' => ['POST', 'GET, POST, PUT', true],
            'comma list miss' => ['DELETE', 'GET, POST, PUT', false],
            'newline list match' => ['POST', "GET\nPOST\nPUT", true],
            'space list match' => ['POST', 'GET POST PUT', true],
            'empty list never matches' => ['anything', '', false],
            'numeric field numeric list' => [42, '40, 42, 44', true],
            'null vs list' => [null, 'x, y', false],
            'whitespace tolerated' => ['POST', '  GET ,  POST  ,  PUT  ', true],
        ];
    }
}
