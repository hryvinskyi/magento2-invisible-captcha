<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\RobotsTxt;

use Hryvinskyi\InvisibleCaptcha\Api\RobotsTxt\GroupInterface;
use Hryvinskyi\InvisibleCaptcha\Api\RobotsTxt\ParserInterface;
use Hryvinskyi\InvisibleCaptcha\Api\RobotsTxt\RuleInterface;

class Parser implements ParserInterface
{
    private const DIRECTIVE_USER_AGENT = 'user-agent';
    private const DIRECTIVE_ALLOW = 'allow';
    private const DIRECTIVE_DISALLOW = 'disallow';

    /**
     * @param GroupFactory $groupFactory
     * @param RuleFactory $ruleFactory
     */
    public function __construct(
        private readonly GroupFactory $groupFactory,
        private readonly RuleFactory $ruleFactory
    ) {
    }

    /**
     * @inheritDoc
     *
     * Consecutive `User-agent` lines stack into one group; the first rule
     * line closes the stack, and the next `User-agent` line then starts a
     * new group. Rules that appear before any `User-agent` line and unknown
     * directives are ignored. An empty Allow/Disallow value adds no rule but
     * still closes the user-agent stack. A group whose rule list stays empty
     * is kept — it marks its agents as allowed everywhere.
     */
    public function parse(string $content): array
    {
        $groups = [];
        $agents = [];
        $rules = [];
        $agentStackClosed = false;

        foreach ($this->splitLines($content) as $line) {
            $separatorPosition = strpos($line, ':');
            if ($separatorPosition === false) {
                continue;
            }

            $directive = strtolower(trim(substr($line, 0, $separatorPosition)));
            $value = trim(substr($line, $separatorPosition + 1));

            if ($directive === self::DIRECTIVE_USER_AGENT) {
                if ($agentStackClosed) {
                    $groups[] = $this->createGroup($agents, $rules);
                    $agents = [];
                    $rules = [];
                    $agentStackClosed = false;
                }
                if ($value !== '') {
                    $agents[] = strtolower($value);
                }
                continue;
            }

            if ($directive !== self::DIRECTIVE_ALLOW && $directive !== self::DIRECTIVE_DISALLOW) {
                continue;
            }
            if ($agents === []) {
                continue;
            }

            $agentStackClosed = true;
            $path = $this->normalizePath($value);
            if ($path !== '') {
                $rules[] = $this->ruleFactory->create([
                    'path' => $path,
                    'isAllow' => $directive === self::DIRECTIVE_ALLOW,
                ]);
            }
        }

        if ($agents !== []) {
            $groups[] = $this->createGroup($agents, $rules);
        }

        return $groups;
    }

    /**
     * Split content into trimmed, comment-free, non-empty lines.
     *
     * @param string $content
     * @return string[]
     */
    private function splitLines(string $content): array
    {
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        $lines = [];
        foreach (preg_split('/\r\n|\r|\n/', $content) ?: [] as $line) {
            $commentStart = strpos($line, '#');
            if ($commentStart !== false) {
                $line = substr($line, 0, $commentStart);
            }
            $line = trim($line);
            if ($line !== '') {
                $lines[] = $line;
            }
        }

        return $lines;
    }

    /**
     * Normalize a rule path pattern: relative patterns get a leading slash so
     * lenient robots.txt files (e.g. `Disallow: checkout`) keep their intent;
     * patterns already anchored by `/` or starting with a `*` wildcard are
     * kept as written.
     *
     * @param string $path
     * @return string
     */
    private function normalizePath(string $path): string
    {
        if ($path === '' || str_starts_with($path, '/') || str_starts_with($path, '*')) {
            return $path;
        }

        return '/' . $path;
    }

    /**
     * Create a group value object from the collected agents and rules.
     *
     * @param string[] $agents
     * @param RuleInterface[] $rules
     * @return GroupInterface
     */
    private function createGroup(array $agents, array $rules): GroupInterface
    {
        return $this->groupFactory->create([
            'userAgents' => $agents,
            'rules' => $rules,
        ]);
    }
}
