<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model;

use Hryvinskyi\InvisibleCaptcha\Api\PathPatternMatcherInterface;

class PathPatternMatcher implements PathPatternMatcherInterface
{
    /** @var array<string, string> Compiled regex per pattern. */
    private array $compiledPatterns = [];

    /**
     * @inheritDoc
     */
    public function matches(string $pattern, string $path): bool
    {
        $regex = $this->compiledPatterns[$pattern] ??= $this->compilePattern($pattern);

        return preg_match($regex, $path) === 1;
    }

    /**
     * @inheritDoc
     */
    public function matchesAny(array $patterns, string $path): bool
    {
        foreach ($patterns as $pattern) {
            if (is_string($pattern) && $pattern !== '' && $this->matches($pattern, $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Compile a robots.txt-style path pattern into an anchored PCRE.
     *
     * @param string $pattern
     * @return string
     */
    private function compilePattern(string $pattern): string
    {
        $anchored = str_ends_with($pattern, '$');
        if ($anchored) {
            $pattern = substr($pattern, 0, -1);
        }

        $body = str_replace('\*', '.*', preg_quote($pattern, '~'));

        return '~^' . $body . ($anchored ? '$' : '') . '~';
    }
}
