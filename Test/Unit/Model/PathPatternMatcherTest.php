<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model;

use Hryvinskyi\InvisibleCaptcha\Model\PathPatternMatcher;
use PHPUnit\Framework\TestCase;

class PathPatternMatcherTest extends TestCase
{
    private PathPatternMatcher $matcher;

    protected function setUp(): void
    {
        $this->matcher = new PathPatternMatcher();
    }

    /**
     * @dataProvider patternProvider
     */
    public function testMatches(string $pattern, string $path, bool $expected): void
    {
        $this->assertSame($expected, $this->matcher->matches($pattern, $path));
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: bool}>
     */
    public static function patternProvider(): array
    {
        return [
            'prefix match' => ['/customer/section/load', '/customer/section/load/', true],
            'prefix with query-less path' => ['/checkout/cart/add', '/checkout/cart/add', true],
            'prefix mismatch' => ['/customer/section/load', '/customer/account', false],
            'wildcard' => ['/*/compare', '/product/compare', true],
            'wildcard mismatch' => ['/*/compare', '/compare', false],
            'end anchor match' => ['/*.pdf$', '/docs/file.pdf', true],
            'end anchor mismatch' => ['/*.pdf$', '/docs/file.pdfx', false],
            'root matches everything' => ['/', '/anything', true],
            'regex chars stay literal' => ['/path(1)', '/path(1)/x', true],
        ];
    }

    public function testMatchesAny(): void
    {
        $patterns = ['/customer/section/load', '/checkout/sidebar/'];

        $this->assertTrue($this->matcher->matchesAny($patterns, '/checkout/sidebar/removeItem'));
        $this->assertFalse($this->matcher->matchesAny($patterns, '/catalog/category/view'));
        $this->assertFalse($this->matcher->matchesAny([], '/anything'));
        $this->assertFalse($this->matcher->matchesAny(['', 42], '/anything'));
    }
}
