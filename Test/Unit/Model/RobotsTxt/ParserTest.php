<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\RobotsTxt;

use Hryvinskyi\InvisibleCaptcha\Api\RobotsTxt\GroupInterface;
use Hryvinskyi\InvisibleCaptcha\Model\RobotsTxt\Group;
use Hryvinskyi\InvisibleCaptcha\Model\RobotsTxt\GroupFactory;
use Hryvinskyi\InvisibleCaptcha\Model\RobotsTxt\Parser;
use Hryvinskyi\InvisibleCaptcha\Model\RobotsTxt\Rule;
use Hryvinskyi\InvisibleCaptcha\Model\RobotsTxt\RuleFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    /** @var GroupFactory&MockObject */
    private GroupFactory $groupFactory;
    /** @var RuleFactory&MockObject */
    private RuleFactory $ruleFactory;
    private Parser $parser;

    protected function setUp(): void
    {
        $this->groupFactory = $this->getMockBuilder(GroupFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->ruleFactory = $this->getMockBuilder(RuleFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        // Make the factories return real value objects with the data given.
        $this->groupFactory->method('create')->willReturnCallback(
            static fn (array $data): Group => new Group($data['userAgents'], $data['rules'])
        );
        $this->ruleFactory->method('create')->willReturnCallback(
            static fn (array $data): Rule => new Rule($data['path'], $data['isAllow'])
        );

        $this->parser = new Parser($this->groupFactory, $this->ruleFactory);
    }

    public function testEmptyContentYieldsNoGroups(): void
    {
        $this->assertSame([], $this->parser->parse(''));
        $this->assertSame([], $this->parser->parse("\n\n# only a comment\n"));
    }

    public function testParsesWildcardGroupWithRules(): void
    {
        $groups = $this->parser->parse(
            "User-agent: *\nDisallow: /checkout/\nAllow: /checkout/cart\n"
        );

        $this->assertCount(1, $groups);
        $this->assertSame(['*'], $groups[0]->getUserAgents());

        $rules = $groups[0]->getRules();
        $this->assertCount(2, $rules);
        $this->assertSame('/checkout/', $rules[0]->getPath());
        $this->assertFalse($rules[0]->isAllow());
        $this->assertSame('/checkout/cart', $rules[1]->getPath());
        $this->assertTrue($rules[1]->isAllow());
    }

    public function testStackedUserAgentsShareOneGroup(): void
    {
        $groups = $this->parser->parse(
            "User-agent: AhrefsBot\nUser-agent: SemrushBot\nDisallow: /\n"
        );

        $this->assertCount(1, $groups);
        $this->assertSame(['ahrefsbot', 'semrushbot'], $groups[0]->getUserAgents());
        $this->assertCount(1, $groups[0]->getRules());
    }

    public function testRuleLineClosesTheAgentStack(): void
    {
        $groups = $this->parser->parse(
            "User-agent: a\nDisallow: /x\nUser-agent: b\nDisallow: /y\n"
        );

        $this->assertCount(2, $groups);
        $this->assertSame(['a'], $groups[0]->getUserAgents());
        $this->assertSame('/x', $groups[0]->getRules()[0]->getPath());
        $this->assertSame(['b'], $groups[1]->getUserAgents());
        $this->assertSame('/y', $groups[1]->getRules()[0]->getPath());
    }

    public function testCommentsBlankLinesAndBomAreIgnored(): void
    {
        $content = "\xEF\xBB\xBF# global rules\n\nUser-agent: * # everyone\n\nDisallow: /private # keep out\n";
        $groups = $this->parser->parse($content);

        $this->assertCount(1, $groups);
        $this->assertSame(['*'], $groups[0]->getUserAgents());
        $this->assertSame('/private', $groups[0]->getRules()[0]->getPath());
    }

    public function testRulesBeforeAnyUserAgentAreIgnored(): void
    {
        $groups = $this->parser->parse(
            "Disallow: /orphan\nUser-agent: *\nDisallow: /kept\n"
        );

        $this->assertCount(1, $groups);
        $rules = $groups[0]->getRules();
        $this->assertCount(1, $rules);
        $this->assertSame('/kept', $rules[0]->getPath());
    }

    public function testEmptyDisallowAddsNoRuleButClosesTheStack(): void
    {
        $groups = $this->parser->parse(
            "User-agent: googlebot\nDisallow:\nUser-agent: *\nDisallow: /blocked\n"
        );

        $this->assertCount(2, $groups);
        $this->assertSame(['googlebot'], $groups[0]->getUserAgents());
        $this->assertSame([], $groups[0]->getRules());
        $this->assertSame(['*'], $groups[1]->getUserAgents());
        $this->assertCount(1, $groups[1]->getRules());
    }

    /**
     * @dataProvider pathNormalizationProvider
     */
    public function testPathNormalization(string $rawPath, string $expected): void
    {
        $groups = $this->parser->parse("User-agent: *\nDisallow: {$rawPath}\n");

        $this->assertSame($expected, $groups[0]->getRules()[0]->getPath());
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function pathNormalizationProvider(): array
    {
        return [
            'absolute path kept' => ['/checkout', '/checkout'],
            'relative path gets slash' => ['checkout', '/checkout'],
            'wildcard prefix kept' => ['*.pdf$', '*.pdf$'],
            'query pattern kept' => ['/*?price=', '/*?price='],
        ];
    }

    public function testDirectivesAreCaseInsensitive(): void
    {
        $groups = $this->parser->parse("USER-AGENT: *\nDISALLOW: /x\nALLOW: /x/y\n");

        $this->assertCount(1, $groups);
        $this->assertCount(2, $groups[0]->getRules());
    }

    public function testUnknownDirectivesDoNotBreakGrouping(): void
    {
        $groups = $this->parser->parse(
            "User-agent: *\nCrawl-delay: 10\nDisallow: /x\nSitemap: https://example.com/sitemap.xml\n"
        );

        $this->assertCount(1, $groups);
        $rules = $groups[0]->getRules();
        $this->assertCount(1, $rules);
        $this->assertSame('/x', $rules[0]->getPath());
    }

    public function testGroupWithoutRulesIsKeptAtEndOfFile(): void
    {
        $groups = $this->parser->parse("User-agent: googlebot\n");

        $this->assertCount(1, $groups);
        $this->assertInstanceOf(GroupInterface::class, $groups[0]);
        $this->assertSame(['googlebot'], $groups[0]->getUserAgents());
        $this->assertSame([], $groups[0]->getRules());
    }

    public function testLinesWithoutColonAreSkipped(): void
    {
        $groups = $this->parser->parse("User-agent: *\nnot a directive\nDisallow: /x\n");

        $this->assertCount(1, $groups);
        $this->assertCount(1, $groups[0]->getRules());
    }
}
