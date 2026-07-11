<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\RobotsTxt;

use Hryvinskyi\InvisibleCaptcha\Api\RobotsTxt\ParserInterface;
use Hryvinskyi\InvisibleCaptcha\Api\RobotsTxt\SourceInterface;
use Hryvinskyi\InvisibleCaptcha\Model\RobotsTxt\Group;
use Hryvinskyi\InvisibleCaptcha\Model\RobotsTxt\Matcher;
use Hryvinskyi\InvisibleCaptcha\Model\RobotsTxt\Rule;
use PHPUnit\Framework\TestCase;

class MatcherTest extends TestCase
{
    private const UA_BROWSER = 'Mozilla/5.0 (Macintosh) AppleWebKit/605.1.15';

    public function testEmptyContentNeverDisallows(): void
    {
        $source = $this->createMock(SourceInterface::class);
        $source->method('getContent')->willReturn("   \n  ");
        $parser = $this->createMock(ParserInterface::class);
        $parser->expects($this->never())->method('parse');

        $matcher = new Matcher($source, $parser);

        $this->assertFalse($matcher->isDisallowed('/checkout', self::UA_BROWSER));
    }

    public function testNoGroupMatchingUserAgentAllowsEverything(): void
    {
        $matcher = $this->matcherFor([
            new Group(['ahrefsbot'], [$this->disallow('/')]),
        ]);

        $this->assertFalse($matcher->isDisallowed('/checkout', self::UA_BROWSER));
    }

    public function testDisallowIsPrefixMatched(): void
    {
        $matcher = $this->matcherFor([
            new Group(['*'], [$this->disallow('/checkout')]),
        ]);

        $this->assertTrue($matcher->isDisallowed('/checkout', self::UA_BROWSER));
        $this->assertTrue($matcher->isDisallowed('/checkout/cart', self::UA_BROWSER));
        $this->assertTrue($matcher->isDisallowed('/checkout?step=1', self::UA_BROWSER));
        $this->assertFalse($matcher->isDisallowed('/catalog/category', self::UA_BROWSER));
        $this->assertFalse($matcher->isDisallowed('/', self::UA_BROWSER));
    }

    public function testMoreSpecificAllowWins(): void
    {
        $matcher = $this->matcherFor([
            new Group(['*'], [$this->disallow('/checkout/'), $this->allow('/checkout/cart')]),
        ]);

        $this->assertTrue($matcher->isDisallowed('/checkout/onepage', self::UA_BROWSER));
        $this->assertFalse($matcher->isDisallowed('/checkout/cart', self::UA_BROWSER));
        $this->assertFalse($matcher->isDisallowed('/checkout/cart/add', self::UA_BROWSER));
    }

    public function testAllowWinsASpecificityTie(): void
    {
        $matcher = $this->matcherFor([
            new Group(['*'], [$this->disallow('/dir'), $this->allow('/dir')]),
        ]);

        $this->assertFalse($matcher->isDisallowed('/dir/page', self::UA_BROWSER));
    }

    public function testLongerDisallowBeatsShorterAllow(): void
    {
        $matcher = $this->matcherFor([
            new Group(['*'], [$this->allow('/media'), $this->disallow('/media/private')]),
        ]);

        $this->assertFalse($matcher->isDisallowed('/media/logo.png', self::UA_BROWSER));
        $this->assertTrue($matcher->isDisallowed('/media/private/file', self::UA_BROWSER));
    }

    /**
     * @dataProvider wildcardProvider
     */
    public function testWildcardAndAnchorMatching(string $pattern, string $uri, bool $expected): void
    {
        $matcher = $this->matcherFor([
            new Group(['*'], [$this->disallow($pattern)]),
        ]);

        $this->assertSame($expected, $matcher->isDisallowed($uri, self::UA_BROWSER));
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: bool}>
     */
    public static function wildcardProvider(): array
    {
        return [
            'star matches any sequence' => ['/*/compare', '/product/compare', true],
            'star mismatch' => ['/*/compare', '/compare', false],
            'anchored extension match' => ['/*.pdf$', '/docs/manual.pdf', true],
            'anchored extension mismatch' => ['/*.pdf$', '/docs/manual.pdfx', false],
            'query param pattern' => ['/*?price=', '/lamps?price=10-20', true],
            'query param pattern no query' => ['/*?price=', '/lamps', false],
            'any query string' => ['/*?', '/lamps?color=blue', true],
            'any query string without query' => ['/*?', '/lamps', false],
            'root disallow' => ['/', '/anything/at/all', true],
            'regex chars are literal' => ['/path(1)', '/path(1)/x', true],
        ];
    }

    public function testSpecificAgentGroupShadowsWildcardGroup(): void
    {
        $matcher = $this->matcherFor([
            new Group(['*'], [$this->disallow('/')]),
            new Group(['googlebot'], []),
        ]);

        // Googlebot has its own (empty → allow-all) group; everyone else hits the catch-all.
        $this->assertFalse($matcher->isDisallowed('/page', 'Mozilla/5.0 (compatible; Googlebot/2.1)'));
        $this->assertTrue($matcher->isDisallowed('/page', self::UA_BROWSER));
    }

    public function testLongestAgentTokenWins(): void
    {
        $matcher = $this->matcherFor([
            new Group(['bot'], [$this->disallow('/a')]),
            new Group(['badbot'], [$this->disallow('/b')]),
        ]);

        $this->assertFalse($matcher->isDisallowed('/a', 'super-BadBot/1.0'));
        $this->assertTrue($matcher->isDisallowed('/b', 'super-BadBot/1.0'));
    }

    public function testGroupsTiedAtWinningSpecificityMerge(): void
    {
        $matcher = $this->matcherFor([
            new Group(['*'], [$this->disallow('/a')]),
            new Group(['*'], [$this->disallow('/b')]),
        ]);

        $this->assertTrue($matcher->isDisallowed('/a', self::UA_BROWSER));
        $this->assertTrue($matcher->isDisallowed('/b', self::UA_BROWSER));
    }

    public function testTargetNormalization(): void
    {
        $matcher = $this->matcherFor([
            new Group(['*'], [$this->disallow('/checkout')]),
        ]);

        $this->assertTrue($matcher->isDisallowed('checkout', self::UA_BROWSER));
        $this->assertTrue($matcher->isDisallowed('/checkout#fragment', self::UA_BROWSER));
    }

    public function testEmptyUserAgentStillMatchesWildcardGroup(): void
    {
        $matcher = $this->matcherFor([
            new Group(['*'], [$this->disallow('/checkout')]),
        ]);

        $this->assertTrue($matcher->isDisallowed('/checkout', ''));
    }

    /**
     * Build a matcher whose source yields non-empty content parsed into the
     * given groups.
     *
     * @param \Hryvinskyi\InvisibleCaptcha\Api\RobotsTxt\GroupInterface[] $groups
     * @return Matcher
     */
    private function matcherFor(array $groups): Matcher
    {
        $source = $this->createMock(SourceInterface::class);
        $source->method('getContent')->willReturn('User-agent: …');
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('parse')->willReturn($groups);

        return new Matcher($source, $parser);
    }

    private function allow(string $path): Rule
    {
        return new Rule($path, true);
    }

    private function disallow(string $path): Rule
    {
        return new Rule($path, false);
    }
}
