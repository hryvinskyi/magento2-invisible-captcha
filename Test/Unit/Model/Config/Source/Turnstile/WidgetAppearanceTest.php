<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Config\Source\Turnstile;

use Hryvinskyi\InvisibleCaptcha\Model\Config\Source\Turnstile\WidgetAppearance;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class WidgetAppearanceTest extends TestCase
{
    private WidgetAppearance $source;

    protected function setUp(): void
    {
        $this->source = new WidgetAppearance();
    }

    public function testToOptionArrayReturnsAllAppearances(): void
    {
        $options = $this->source->toOptionArray();

        $this->assertCount(3, $options);
        $this->assertSame(['always', 'execute', 'interaction-only'], array_column($options, 'value'));
    }

    #[DataProvider('appearanceProvider')]
    public function testOptionLabel(int $index, string $value, string $label): void
    {
        $option = $this->source->toOptionArray()[$index];

        $this->assertSame($value, $option['value']);
        $this->assertSame($label, (string)$option['label']);
    }

    /**
     * @return array<string, array{int, string, string}>
     */
    public static function appearanceProvider(): array
    {
        return [
            'always' => [0, 'always', 'Always visible'],
            'execute' => [1, 'execute', 'Visible only after execute()'],
            'interaction-only' => [
                2,
                'interaction-only',
                'Visible only when interaction needed (invisible-like)',
            ],
        ];
    }
}
