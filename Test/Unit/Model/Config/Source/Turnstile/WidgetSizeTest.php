<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Config\Source\Turnstile;

use Hryvinskyi\InvisibleCaptcha\Model\Config\Source\Turnstile\WidgetSize;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class WidgetSizeTest extends TestCase
{
    private WidgetSize $source;

    protected function setUp(): void
    {
        $this->source = new WidgetSize();
    }

    public function testToOptionArrayReturnsAllSizes(): void
    {
        $options = $this->source->toOptionArray();

        $this->assertCount(3, $options);
        $this->assertSame(['flexible', 'normal', 'compact'], array_column($options, 'value'));
    }

    #[DataProvider('sizeProvider')]
    public function testOptionLabel(int $index, string $value, string $label): void
    {
        $option = $this->source->toOptionArray()[$index];

        $this->assertSame($value, $option['value']);
        $this->assertSame($label, (string)$option['label']);
    }

    /**
     * @return array<string, array{int, string, string}>
     */
    public static function sizeProvider(): array
    {
        return [
            'flexible' => [0, 'flexible', 'Flexible (auto-width)'],
            'normal' => [1, 'normal', 'Normal (300x65)'],
            'compact' => [2, 'compact', 'Compact (150x140)'],
        ];
    }
}
