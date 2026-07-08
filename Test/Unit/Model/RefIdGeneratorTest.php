<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model;

use Hryvinskyi\InvisibleCaptcha\Model\RefIdGenerator;
use PHPUnit\Framework\TestCase;

class RefIdGeneratorTest extends TestCase
{
    private RefIdGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new RefIdGenerator();
    }

    public function testGenerateReturnsEightHexUppercaseChars(): void
    {
        $token = $this->generator->generate();
        $this->assertMatchesRegularExpression('/^[A-F0-9]{8}$/', $token);
    }

    public function testGenerateProducesDistinctTokens(): void
    {
        $tokens = [];
        for ($i = 0; $i < 20; $i++) {
            $tokens[] = $this->generator->generate();
        }
        $this->assertCount(20, array_unique($tokens), 'generate() should be effectively unique');
    }

    /**
     * @dataProvider validityProvider
     */
    public function testIsValid(string $token, bool $expected): void
    {
        $this->assertSame($expected, $this->generator->isValid($token));
    }

    public static function validityProvider(): array
    {
        return [
            'valid alnum mix'    => ['A7F23K9M', true], // pattern is [A-Z0-9]{8}, so K and M are accepted
            'valid alnum'        => ['ABC12345', true],
            'too short'          => ['ABC123', false],
            'too long'           => ['ABCDEFGHI', false],
            'lowercase rejected' => ['abcdef12', false],
            'symbols rejected'   => ['ABC!@#$%', false],
            'empty'              => ['', false],
        ];
    }

    public function testFormatProducesGroupedToken(): void
    {
        $this->assertSame('A7F2 · 3K9M', $this->generator->format('A7F23K9M'));
    }

    public function testFormatRejectsInvalidInput(): void
    {
        $this->assertSame('', $this->generator->format('bad'));
        $this->assertSame('', $this->generator->format(''));
    }
}
