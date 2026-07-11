<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api\RobotsTxt;

/**
 * Resolves the robots.txt content the current store actually serves.
 */
interface SourceInterface
{
    /**
     * Get the robots.txt content for the current store — the physical
     * pub/robots.txt when one exists (the web server serves it directly),
     * otherwise the "Search Engine Robots" custom instructions rendered by
     * Magento at /robots.txt. Empty string when neither is available.
     *
     * @return string
     */
    public function getContent(): string;
}
