<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api;

/**
 * Decides whether the current request comes from a crawler that may skip the
 * route-level challenge.
 *
 * The module's own implementation trusts the User-Agent: a request whose
 * User-Agent contains one of the "Excluded User Agents" substrings is exempt.
 * Anyone can send such a User-Agent, so a module that can tell a real crawler
 * from one posing as it replaces or decorates this contract through a di.xml
 * preference.
 *
 * @api
 */
interface CrawlerExemptionInterface
{
    /**
     * Whether the current request is a crawler allowed to skip the challenge.
     *
     * @return bool
     */
    public function isExempt(): bool;
}
