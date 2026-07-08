<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Config\Source\Recaptcha;

use Hryvinskyi\InvisibleCaptcha\Model\Config\Source\Recaptcha\Theme;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ThemeTest extends TestCase
{
    private Theme $source;

    protected function setUp(): void
    {
        $this->source = new Theme();
    }

    public function testToOptionArrayReturnsBothThemes(): void
    {
        $options = $this->source->toOptionArray();

        $this->assertCount(2, $options);
        $this->assertSame(['light', 'dark'], array_column($options, 'value'));
    }

    #[DataProvider('themeProvider')]
    public function testOptionLabel(int $index, string $value, string $label): void
    {
        $option = $this->source->toOptionArray()[$index];

        $this->assertSame($value, $option['value']);
        $this->assertSame($label, (string)$option['label']);
    }

    /**
     * @return array<string, array{int, string, string}>
     */
    public static function themeProvider(): array
    {
        return [
            'light' => [0, 'light', 'Light'],
            'dark' => [1, 'dark', 'Dark'],
        ];
    }
}
