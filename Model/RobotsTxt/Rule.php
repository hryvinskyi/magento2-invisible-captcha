<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\RobotsTxt;

use Hryvinskyi\InvisibleCaptcha\Api\RobotsTxt\RuleInterface;

class Rule implements RuleInterface
{
    /**
     * @param string $path
     * @param bool $isAllow
     */
    public function __construct(
        private readonly string $path,
        private readonly bool $isAllow
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @inheritDoc
     */
    public function isAllow(): bool
    {
        return $this->isAllow;
    }
}
