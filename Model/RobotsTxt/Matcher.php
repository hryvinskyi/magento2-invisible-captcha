<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\RobotsTxt;

use Hryvinskyi\InvisibleCaptcha\Api\PathPatternMatcherInterface;
use Hryvinskyi\InvisibleCaptcha\Api\RobotsTxt\GroupInterface;
use Hryvinskyi\InvisibleCaptcha\Api\RobotsTxt\MatcherInterface;
use Hryvinskyi\InvisibleCaptcha\Api\RobotsTxt\ParserInterface;
use Hryvinskyi\InvisibleCaptcha\Api\RobotsTxt\RuleInterface;
use Hryvinskyi\InvisibleCaptcha\Api\RobotsTxt\SourceInterface;

class Matcher implements MatcherInterface
{
    private const WILDCARD_AGENT = '*';

    /** @var array<string, GroupInterface[]> Parsed groups keyed by content hash. */
    private array $groupsByContent = [];

    /**
     * @param SourceInterface $source
     * @param ParserInterface $parser
     * @param PathPatternMatcherInterface $pathPatternMatcher
     */
    public function __construct(
        private readonly SourceInterface $source,
        private readonly ParserInterface $parser,
        private readonly PathPatternMatcherInterface $pathPatternMatcher
    ) {
    }

    /**
     * @inheritDoc
     */
    public function isDisallowed(string $uri, string $userAgent): bool
    {
        $content = trim($this->source->getContent());
        if ($content === '') {
            return false;
        }

        $rules = $this->selectApplicableRules($this->getGroups($content), $userAgent);
        if ($rules === []) {
            return false;
        }

        $target = $this->normalizeTarget($uri);
        $longestAllow = -1;
        $longestDisallow = -1;

        foreach ($rules as $rule) {
            if (!$this->pathPatternMatcher->matches($rule->getPath(), $target)) {
                continue;
            }

            $specificity = strlen($rule->getPath());
            if ($rule->isAllow()) {
                $longestAllow = max($longestAllow, $specificity);
            } else {
                $longestDisallow = max($longestDisallow, $specificity);
            }
        }

        return $longestDisallow > $longestAllow;
    }

    /**
     * Parse the content once per distinct robots.txt body.
     *
     * @param string $content
     * @return GroupInterface[]
     */
    private function getGroups(string $content): array
    {
        $key = sha1($content);

        return $this->groupsByContent[$key] ??= $this->parser->parse($content);
    }

    /**
     * Combine the rules of every group declared at the highest matching
     * user-agent specificity: the longest agent token contained in the
     * user-agent string wins, the `*` group is the fallback, and groups tied
     * at the winning specificity merge (RFC 9309 combines equal groups).
     *
     * @param GroupInterface[] $groups
     * @param string $userAgent
     * @return RuleInterface[]
     */
    private function selectApplicableRules(array $groups, string $userAgent): array
    {
        $userAgent = strtolower($userAgent);

        $winningSpecificity = -1;
        foreach ($groups as $group) {
            $winningSpecificity = max($winningSpecificity, $this->groupSpecificity($group, $userAgent));
        }
        if ($winningSpecificity < 0) {
            return [];
        }

        $rules = [];
        foreach ($groups as $group) {
            if ($this->groupSpecificity($group, $userAgent) === $winningSpecificity) {
                $rules[] = $group->getRules();
            }
        }

        return array_merge(...$rules);
    }

    /**
     * How specifically a group's agents match the user agent: token length
     * for a contained token, 0 for the `*` wildcard, -1 for no match.
     *
     * @param GroupInterface $group
     * @param string $userAgent Lower-cased User-Agent header
     * @return int
     */
    private function groupSpecificity(GroupInterface $group, string $userAgent): int
    {
        $specificity = -1;
        foreach ($group->getUserAgents() as $agentToken) {
            if ($agentToken === self::WILDCARD_AGENT) {
                $specificity = max($specificity, 0);
                continue;
            }
            if ($userAgent !== '' && str_contains($userAgent, $agentToken)) {
                $specificity = max($specificity, strlen($agentToken));
            }
        }

        return $specificity;
    }

    /**
     * Normalize the request URI into the robots.txt match target: drop the
     * fragment and guarantee a leading slash. The query string is kept —
     * robots.txt patterns match against path + query.
     *
     * @param string $uri
     * @return string
     */
    private function normalizeTarget(string $uri): string
    {
        $fragmentStart = strpos($uri, '#');
        if ($fragmentStart !== false) {
            $uri = substr($uri, 0, $fragmentStart);
        }

        if ($uri === '' || $uri[0] !== '/') {
            $uri = '/' . $uri;
        }

        return $uri;
    }
}
