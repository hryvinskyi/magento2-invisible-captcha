<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Config\Source\Recaptcha;

use Hryvinskyi\InvisibleCaptcha\Model\Config\Source\Recaptcha\V2Size;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class V2SizeTest extends TestCase
{
    private V2Size $source;

    protected function setUp(): void
    {
        $this->source = new V2Size();
    }

    public function testToOptionArrayReturnsBothSizes(): void
    {
        $options = $this->source->toOptionArray();

        $this->assertCount(2, $options);
        $this->assertSame(['normal', 'compact'], array_column($options, 'value'));
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
            'normal' => [0, 'normal', 'Normal'],
            'compact' => [1, 'compact', 'Compact'],
        ];
    }
}
