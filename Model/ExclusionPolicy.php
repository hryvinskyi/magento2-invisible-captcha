<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Api\ExclusionPolicyInterface;
use Hryvinskyi\InvisibleCaptcha\Api\PathPatternMatcherInterface;

class ExclusionPolicy implements ExclusionPolicyInterface
{
    /**
     * @param ConfigInterface $config
     * @param PathPatternMatcherInterface $pathPatternMatcher
     */
    public function __construct(
        private readonly ConfigInterface $config,
        private readonly PathPatternMatcherInterface $pathPatternMatcher
    ) {
    }

    /**
     * @inheritDoc
     */
    public function isIpExcluded(string $ip, ?string $scopeCode = null): bool
    {
        if ($ip === '') {
            return false;
        }

        $excludedIps = $this->config->getExcludedIps($scopeCode);

        return $excludedIps !== [] && in_array($ip, $excludedIps, true);
    }

    /**
     * @inheritDoc
     */
    public function isUserAgentExcluded(string $userAgent, ?string $scopeCode = null): bool
    {
        if ($userAgent === '') {
            return false;
        }

        foreach ($this->config->getExcludedUserAgents($scopeCode) as $excluded) {
            if ($excluded !== '' && stripos($userAgent, $excluded) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function isPathExcluded(string $path, ?string $scopeCode = null): bool
    {
        $patterns = $this->config->getExcludedPaths($scopeCode);
        if ($patterns === []) {
            return false;
        }

        return $this->pathPatternMatcher->matchesAny(
            array_map([$this, 'normalizePattern'], $patterns),
            $this->normalizePath($path)
        );
    }

    /**
     * Give a relative pattern its leading slash; `/`- and `*`-anchored
     * patterns are kept as written (robots.txt semantics).
     *
     * @param string $pattern
     * @return string
     */
    private function normalizePattern(string $pattern): string
    {
        if ($pattern === '' || str_starts_with($pattern, '/') || str_starts_with($pattern, '*')) {
            return $pattern;
        }

        return '/' . $pattern;
    }

    /**
     * Normalize the request path to a leading slash for matching.
     *
     * @param string $path
     * @return string
     */
    private function normalizePath(string $path): string
    {
        return '/' . ltrim($path, '/');
    }
}
