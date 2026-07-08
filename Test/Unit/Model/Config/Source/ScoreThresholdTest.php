<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Config\Source;

use Hryvinskyi\InvisibleCaptcha\Model\Config\Source\ScoreThreshold;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ScoreThresholdTest extends TestCase
{
    private ScoreThreshold $source;

    protected function setUp(): void
    {
        $this->source = new ScoreThreshold();
    }

    public function testToOptionArrayReturnsNineOptions(): void
    {
        $options = $this->source->toOptionArray();

        $this->assertCount(9, $options);
        $this->assertSame('0.1', $options[0]['value']);
        $this->assertSame('0.1', $options[0]['label']);
        $this->assertSame('0.9', $options[8]['value']);
        $this->assertSame('0.9', $options[8]['label']);
    }

    public function testValuesAndLabelsAreEqualStrings(): void
    {
        $expected = ['0.1', '0.2', '0.3', '0.4', '0.5', '0.6', '0.7', '0.8', '0.9'];

        $values = array_column($this->source->toOptionArray(), 'value');
        $labels = array_column($this->source->toOptionArray(), 'label');

        $this->assertSame($expected, $values);
        $this->assertSame($expected, $labels);
    }

    #[DataProvider('thresholdProvider')]
    public function testEachThresholdIsAStringPair(int $index, string $expected): void
    {
        $option = $this->source->toOptionArray()[$index];

        $this->assertSame($expected, $option['value']);
        $this->assertSame($expected, $option['label']);
        $this->assertIsString($option['value']);
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function thresholdProvider(): array
    {
        return [
            'first' => [0, '0.1'],
            'mid' => [4, '0.5'],
            'last' => [8, '0.9'],
        ];
    }
}
